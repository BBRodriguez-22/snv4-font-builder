<?php
class Converter {
    public string $base;
    public string $scanDir;
    public string $outputDir;
    public array $slots = ['hum'=>'Hum','in'=>'Ignition','out'=>'Retraction','clsh'=>'Clash','blst'=>'Blaster','swng'=>'Swing','swingh'=>'Swing High','swingl'=>'Swing Low','stab'=>'Stab','force'=>'Force','font'=>'Font','drag'=>'Drag','enddrag'=>'End Drag','lock'=>'Lockup','endlock'=>'End Lockup','melt'=>'Melt','endmelt'=>'End Melt'];
    public array $coreAliases = ['hum'=>['hum','humm','idle','loop','mainhum'],'in'=>['in','poweron','ignite','ignition','on','bladeon','start'],'out'=>['out','poweroff','retract','retraction','off','bladeoff','shutdown'],'clsh'=>['clsh','clash'],'blst'=>['blst','blaster','blast'],'swng'=>['swng','swing'],'swingh'=>['swingh','swinghigh','highswing','swngh'],'swingl'=>['swingl','swinglow','lowswing','swngl'],'stab'=>['stab'],'force'=>['force'],'font'=>['font'],'drag'=>['drag','bgndrag','begindrag'],'enddrag'=>['enddrag'],'lock'=>['lock','bgnlock','beginlock'],'endlock'=>['endlock'],'melt'=>['melt','bgnmelt','beginmelt'],'endmelt'=>['endmelt']];
    public array $sourceGroups = ['hum'=>['hum'],'in'=>['in','poweron','ignite','ignition','on','bladeon','start'],'out'=>['out','poweroff','retract','retraction','off','bladeoff','shutdown'],'clsh'=>['clsh','clash'],'blst'=>['blst','blaster','blast'],'swng'=>['swng','swing'],'swingh'=>['swingh','swinghigh','highswing','swngh'],'swingl'=>['swingl','swinglow','lowswing','swngl'],'stab'=>['stab'],'force'=>['force'],'font'=>['font'],'drag'=>['drag'],'bgndrag'=>['bgndrag'],'begindrag'=>['begindrag'],'enddrag'=>['enddrag'],'lock'=>['lock'],'bgnlock'=>['bgnlock'],'beginlock'=>['beginlock'],'endlock'=>['endlock'],'melt'=>['melt'],'bgnmelt'=>['bgnmelt'],'beginmelt'=>['beginmelt'],'endmelt'=>['endmelt']];
    public array $extraAliases = ['boot'=>['boot'],'preon'=>['preon'],'spin'=>['spin'],'track'=>['track','tracks']];
    public array $ignoredAliases = ['lightingblock'=>['lightingblock','beginlightingblock','endlightingblock'],'side'=>['sidein','sideout']];
    public array $ignoreRootNames = ['extra','extras','bonus','optional','optionals','unused','alternative','alternatives'];
    public array $mergePromptSets = ['drag'=>[['bgndrag','begindrag'],['drag']],'lock'=>[['bgnlock','beginlock'],['lock']],'melt'=>[['bgnmelt','beginmelt'],['melt']]];

    public function __construct(string $base,string $scanDir,string $outputDir){$this->base=$base;$this->scanDir=$scanDir;$this->outputDir=$outputDir;$this->ensureDir($scanDir);$this->ensureDir($outputDir);}

    public function findCandidateRoots(bool $includeExtras=false): array {
        $wavFiles=$this->listWavs($this->scanDir); $map=[];
        foreach($wavFiles as $wav){
            $root=$this->deriveCandidateRootFromFile($wav); if(!$root||!is_dir($root)) continue;
            $root=realpath($root); if($root===false) continue;
            $c=$this->classifyFile($wav,$root); if($c['type']==='unmatched') continue;
            $baseName=strtolower(basename($root)); if(!$includeExtras && in_array($baseName,$this->ignoreRootNames,true)) continue;
            if(!isset($map[$root])) $map[$root]=['name'=>basename($root),'path'=>$root,'display'=>$this->relPath($root,$this->scanDir),'wav_count'=>0,'score'=>0,'groups'=>[],'direct'=>0];
            $map[$root]['wav_count']++;
            $map[$root]['groups'][$c['kind'] ?: $c['slot']] = true;
            $map[$root]['score'] += $c['type']==='core' ? 120 : ($c['type']==='extra' ? 30 : 10);
            $rel=$this->relPath($wav,$root); if(count(explode('/',str_replace('\\','/',$rel)))===1){$map[$root]['direct']++;$map[$root]['score']+=20;}
        }
        $out=[];
        foreach($map as $item){ if(count($item['groups'])<2 && $item['direct']<4) continue; $item['score']+=min($item['wav_count'],200)*4; unset($item['groups'],$item['direct']); $out[]=$item; }
        usort($out,function($a,$b){ if($a['score']===$b['score']){ if($a['wav_count']===$b['wav_count']) return strnatcasecmp($a['display'],$b['display']); return $b['wav_count']<=>$a['wav_count']; } return $b['score']<=>$a['score']; });
        return $this->dedupeNested($out);
    }

    public function analyzeRoot(string $root): array {
        $a=['root'=>$root,'files'=>[],'core'=>[],'extras'=>[],'ignored'=>[],'unmatched'=>[]];
        foreach($this->listWavs($root) as $path){
            $i=$this->classifyFile($path,$root); $i['src']=$path; $i['rel']=$this->relPath($path,$root); $a['files'][]=$i;
            if($i['type']==='core') $a['core'][$i['slot']][]=$i; elseif($i['type']==='extra') $a['extras'][]=$i; elseif($i['type']==='ignored') $a['ignored'][]=$i; else $a['unmatched'][]=$i;
        }
        foreach(array_keys($this->slots) as $slot) if(!isset($a['core'][$slot])) $a['core'][$slot]=[];
        return $a;
    }

    public function buildPlan(array $analysis,array $mergeChoices=[],array $manualAssignments=[]): array {
        $core=[]; foreach(array_keys($this->slots) as $slot) $core[$slot]=[];
        foreach($analysis['core'] as $slot=>$items){
            $items=$this->sortItems($items);
            if(isset($this->mergePromptSets[$slot])){
                [$beginAliases,$baseAliases]=$this->mergePromptSets[$slot]; $begin=[]; $base=[];
                foreach($items as $item){ $g=strtolower($item['source_group']); if(in_array($g,$beginAliases,true)) $begin[]=$item; else $base[]=$item; }
                if($begin && $base){ $choice=$mergeChoices[$slot]??'merge'; $items=$choice==='begin'?$begin:($choice==='base'?$base:array_merge($begin,$base)); $items=$this->sortItems($items); }
            }
            $core[$slot]=$items;
        }
        foreach($manualAssignments as $src=>$slot){
            if($slot===''||$slot==='skip') continue;
            foreach($analysis['unmatched'] as $item){ if($item['src']===$src){ $item['confidence']='manual'; $item['source_group']='manual'; $core[$slot][]=$item; break; } }
        }
        foreach($core as $slot=>$items) $core[$slot]=$this->sortItems($items);
        $summary=['auto'=>0,'review'=>0,'extras'=>$analysis['extras'],'ignored'=>$analysis['ignored'],'unmatched'=>[]];
        foreach($core as $items) foreach($items as $item){ if(($item['confidence']??'')==='manual') $summary['review']++; else $summary['auto']++; }
        foreach($analysis['unmatched'] as $item){ $assigned=false; foreach($manualAssignments as $src=>$slot){ if($src===$item['src']&&$slot!==''&&$slot!=='skip'){ $assigned=true; break; } } if(!$assigned) $summary['unmatched'][]=$item; }
        return ['core'=>$core,'summary'=>$summary];
    }

    public function buildOutput(string $outputName,array $coreAssignments): array {
        $name=$this->cleanOutputName($outputName); $dest=$this->outputDir.DIRECTORY_SEPARATOR.$name; if(is_dir($dest)) $this->rrmdir($dest); $this->ensureDir($dest);
        foreach($coreAssignments as $slot=>$items){ if(!$items) continue; $slotDir=$dest.DIRECTORY_SEPARATOR.$slot; $this->ensureDir($slotDir); $n=1; foreach($items as $item){ @copy($item['src'],$slotDir.DIRECTORY_SEPARATOR.$slot.str_pad((string)$n,2,'0',STR_PAD_LEFT).'.wav'); $n++; } }
        $log=$dest.DIRECTORY_SEPARATOR.'log.txt'; file_put_contents($log,"SNV4 Font Builder Log\nBuilt: ".$name."\n"); return ['dest'=>$dest,'log'=>$log,'name'=>$name];
    }

    public function compactCounts(array $core): array { $c=[]; foreach($core as $slot=>$items) if(count($items)>0) $c[$slot]=count($items); ksort($c); return $c; }

    private function deriveCandidateRootFromFile(string $path): ?string {
        $dir=dirname($path); $parent=$this->normalizeToken(basename($dir));
        foreach([$this->sourceGroups,$this->extraAliases,$this->ignoredAliases] as $set){ foreach($set as $aliases){ foreach($aliases as $alias){ if($parent===$this->normalizeToken($alias)){ $candidate=dirname($dir); return is_dir($candidate)?$candidate:$dir; }}}}
        return $dir;
    }

    private function dedupeNested(array $candidates): array { $kept=[]; foreach($candidates as $cand){ $skip=False; foreach($kept as $existing){ if($this->isAncestorPath($cand['path'],$existing['path'])){$skip=True; break;} } if(!$skip)$kept[]=$cand; } return $kept; }

    private function classifyFile(string $fullPath,string $root): array {
        $rel=$this->relPath($fullPath,$root); $parts=explode('/',str_replace('\\','/',$rel)); $source=count($parts)>1?$this->normalizeToken($parts[count($parts)-2]):''; $token=$this->normalizeToken(pathinfo(basename($fullPath),PATHINFO_FILENAME));
        if($source!==''){
            foreach($this->sourceGroups as $group=>$aliases){ foreach($aliases as $alias){ if($source===$this->normalizeToken($alias)){ if(isset($this->slots[$group])) return ['type'=>'core','slot'=>$group,'kind'=>$group,'confidence'=>'high','source_group'=>$source]; if(in_array($group,['bgndrag','begindrag'],true)) return ['type'=>'core','slot'=>'drag','kind'=>'drag','confidence'=>'high','source_group'=>$source]; if(in_array($group,['bgnlock','beginlock'],true)) return ['type'=>'core','slot'=>'lock','kind'=>'lock','confidence'=>'high','source_group'=>$source]; if(in_array($group,['bgnmelt','beginmelt'],true)) return ['type'=>'core','slot'=>'melt','kind'=>'melt','confidence'=>'high','source_group'=>$source]; } } }
            foreach($this->extraAliases as $kind=>$aliases){ foreach($aliases as $alias) if($source===$this->normalizeToken($alias)) return ['type'=>'extra','slot'=>'','kind'=>$kind,'confidence'=>'medium','source_group'=>$source];}
            foreach($this->ignoredAliases as $kind=>$aliases){ foreach($aliases as $alias) if($source===$this->normalizeToken($alias)) return ['type'=>'ignored','slot'=>'','kind'=>$kind,'confidence'=>'medium','source_group'=>$source];}
        }
        foreach($this->coreAliases as $slot=>$aliases){ foreach($aliases as $alias){ $a=$this->normalizeToken($alias); if($token===$a||preg_match('/^'.preg_quote($a,'/').'\d+$/',$token)) return ['type'=>'core','slot'=>$slot,'kind'=>$slot,'confidence'=>'high','source_group'=>$source!==''?$source:$a]; } }
        foreach($this->extraAliases as $kind=>$aliases){ foreach($aliases as $alias){ $a=$this->normalizeToken($alias); if($token===$a||preg_match('/^'.preg_quote($a,'/').'\d+$/',$token)) return ['type'=>'extra','slot'=>'','kind'=>$kind,'confidence'=>'medium','source_group'=>$source]; } }
        foreach($this->ignoredAliases as $kind=>$aliases){ foreach($aliases as $alias){ $a=$this->normalizeToken($alias); if($token===$a||preg_match('/^'.preg_quote($a,'/').'\d+$/',$token)) return ['type'=>'ignored','slot'=>'','kind'=>$kind,'confidence'=>'medium','source_group'=>$source]; } }
        return ['type'=>'unmatched','slot'=>'','kind'=>'','confidence'=>'none','source_group'=>$source];
    }

    private function listWavs(string $dir): array { $files=[]; if(!is_dir($dir)) return $files; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)); foreach($it as $file) if($file->isFile()&&strtolower($file->getExtension())==='wav') $files[]=$file->getPathname(); usort($files,fn($a,$b)=>strnatcasecmp($a,$b)); return $files; }
    private function normalizeToken(string $text): string { $text=strtolower($text); $text=preg_replace('/\.[a-z0-9]+$/','',$text); $text=preg_replace('/\s*\(\d+\)\s*/','',$text); $text=str_replace(['-','_',' ','.','(',')','[',']','{','}'],'',$text); return preg_replace('/[^a-z0-9]/','',$text); }
    private function relPath(string $path,string $base): string { $b=realpath($base); $p=realpath($path); if($b===false||$p===false) return basename($path); $b=str_replace('\\','/',rtrim($b,DIRECTORY_SEPARATOR)).'/'; $p=str_replace('\\','/',$p); return strpos($p,$b)===0?substr($p,strlen($b)):basename($path); }
    private function isAncestorPath(string $ancestor,string $child): bool { $a=str_replace('\\','/',rtrim((string)realpath($ancestor),'/')); $c=str_replace('\\','/',rtrim((string)realpath($child),'/')); if($a===''||$c===''||$a===$c) return false; return strpos($c.'/', $a.'/')===0; }
    private function sortItems(array $items): array { usort($items,fn($a,$b)=>strnatcasecmp($a['rel'],$b['rel'])); return $items; }
    private function ensureDir(string $dir): void { if(!is_dir($dir)) mkdir($dir,0777,true); }
    private function rrmdir(string $dir): void { if(!is_dir($dir)) return; foreach(scandir($dir) as $item){ if($item==='.'||$item==='..') continue; $path=$dir.DIRECTORY_SEPARATOR.$item; if(is_dir($path)) $this->rrmdir($path); else @unlink($path);} @rmdir($dir); }
    private function cleanOutputName(string $name): string { $name=preg_replace('/[<>:"\/\\\\|?*]+/',' ',$name); $name=preg_replace('/\s+/',' ',$name); $name=trim($name); return $name===''?'Converted_SNV4':$name; }
}
?>