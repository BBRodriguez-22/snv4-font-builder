let currentAudio=null;function playPreview(url,label){const p=document.getElementById('global-audio');const t=document.getElementById('now-playing');if(!p)return;if(currentAudio===url&&!p.paused){p.pause();p.currentTime=0;t.textContent='Stopped';currentAudio=null;return;}currentAudio=url;p.src=url;p.play();t.textContent=label||'Playing';}function stopPreview(){const p=document.getElementById('global-audio');const t=document.getElementById('now-playing');if(!p)return;p.pause();p.currentTime=0;currentAudio=null;t.textContent='Stopped';}
function setupZipUploadForm(){
  const form=document.getElementById('zip-upload-form');
  if(!form) return;
  const wrap=document.getElementById('upload-progress');
  const fill=document.getElementById('upload-progress-fill');
  const text=document.getElementById('upload-progress-text');
  form.addEventListener('submit',function(e){
    e.preventDefault();
    const data=new FormData(form);
    const xhr=new XMLHttpRequest();
    wrap.style.display='grid';
    fill.style.width='0%';
    text.textContent='Uploading ZIP...';
    xhr.upload.addEventListener('progress',function(ev){
      if(ev.lengthComputable){
        const pct=Math.max(3,Math.round((ev.loaded/ev.total)*100));
        fill.style.width=pct+'%';
        text.textContent='Uploading ZIP... '+pct+'%';
      }else{
        fill.style.width='25%';
        text.textContent='Uploading ZIP...';
      }
    });
    xhr.onreadystatechange=function(){
      if(xhr.readyState===4){
        if(xhr.status>=200&&xhr.status<400){
          fill.style.width='100%';
          text.textContent='Processing ZIP...';
          window.location.href='index.php';
        }else{
          text.textContent='Upload failed. Please try again.';
        }
      }
    };
    xhr.open('POST',form.action,true);
    xhr.send(data);
  });
}
window.addEventListener('load',setupZipUploadForm);

function setStatusStep(id, state){
  const el=document.getElementById(id);
  if(!el) return;
  el.classList.remove('active','done');
  if(state) el.classList.add(state);
}
function setupZipUploadForm(){
  const form=document.getElementById('zip-upload-form');
  if(!form) return;
  const wrap=document.getElementById('upload-progress');
  const fill=document.getElementById('upload-progress-fill');
  const text=document.getElementById('upload-progress-text');
  form.addEventListener('submit',function(e){
    e.preventDefault();
    const data=new FormData(form);
    const xhr=new XMLHttpRequest();
    wrap.style.display='grid';
    fill.style.width='0%';
    text.textContent='Uploading ZIP...';
    setStatusStep('status-upload','active');
    setStatusStep('status-extract','');
    setStatusStep('status-scan','');
    xhr.upload.addEventListener('progress',function(ev){
      if(ev.lengthComputable){
        const pct=Math.max(3,Math.round((ev.loaded/ev.total)*100));
        fill.style.width=pct+'%';
        text.textContent='Uploading ZIP... '+pct+'%';
      }else{
        fill.style.width='25%';
        text.textContent='Uploading ZIP...';
      }
    });
    xhr.onreadystatechange=function(){
      if(xhr.readyState===2 || xhr.readyState===3){
        setStatusStep('status-upload','done');
        setStatusStep('status-extract','active');
        text.textContent='Upload complete. Extracting ZIP...';
        fill.style.width='100%';
      }
      if(xhr.readyState===4){
        if(xhr.status>=200&&xhr.status<400){
          setStatusStep('status-upload','done');
          setStatusStep('status-extract','done');
          setStatusStep('status-scan','active');
          text.textContent='ZIP extracted. Scanning soundfont roots...';
          setTimeout(function(){ window.location.href='index.php'; }, 250);
        }else{
          text.textContent='Upload failed. Please try again.';
        }
      }
    };
    xhr.open('POST',form.action,true);
    xhr.send(data);
  });
}
function setupSortableTables(){
  document.querySelectorAll('tbody.sortable-body').forEach(function(tbody){
    let dragging=null;
    tbody.querySelectorAll('tr.drag-row').forEach(function(row){
      row.draggable=true;
      row.addEventListener('dragstart',function(){
        dragging=row;
        row.classList.add('dragging');
      });
      row.addEventListener('dragend',function(){
        row.classList.remove('dragging');
        dragging=null;
        renumberOrders(tbody);
      });
      row.addEventListener('dragover',function(e){
        e.preventDefault();
        const current=row;
        if(!dragging || dragging===current) return;
        const rect=current.getBoundingClientRect();
        const after=e.clientY > rect.top + rect.height/2;
        if(after) current.parentNode.insertBefore(dragging, current.nextSibling);
        else current.parentNode.insertBefore(dragging, current);
      });
    });
  });
}
function renumberOrders(scope){
  const rows=(scope||document).querySelectorAll('tbody.sortable-body tr.drag-row');
  let n=1;
  rows.forEach(function(row){
    const input=row.querySelector('input.order-input');
    if(input) input.value=n++;
  });
}
window.addEventListener('load',function(){
  setupZipUploadForm();
  setupSortableTables();
  renumberOrders(document);
});


function applyTheme(theme){
  document.body.classList.remove('light-theme');
  if(theme==='light'){ document.body.classList.add('light-theme'); }
  document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
    btn.classList.toggle('active', btn.getAttribute('data-theme-btn')===theme);
  });
  try{ localStorage.setItem('snv4_theme', theme); }catch(e){}
}
function setupThemeToggle(){
  let theme='dark';
  try{ theme = localStorage.getItem('snv4_theme') || 'dark'; }catch(e){}
  applyTheme(theme);
  document.querySelectorAll('[data-theme-btn]').forEach(function(btn){
    btn.addEventListener('click', function(){
      applyTheme(btn.getAttribute('data-theme-btn'));
    });
  });
}
window.addEventListener('load', setupThemeToggle);

