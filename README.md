# SNV4 Font Builder

![Version](https://img.shields.io/badge/version-v1.2-blue)
![GitHub release](https://img.shields.io/github/v/release/BBRodriguez-22/snv4-font-builder)
![Downloads](https://img.shields.io/github/downloads/BBRodriguez-22/snv4-font-builder/total)
![License](https://img.shields.io/badge/license-Non--Commercial-red)
![VirusTotal](https://img.shields.io/badge/VirusTotal-0%20detections-brightgreen)
![Platform](https://img.shields.io/badge/platform-Windows-blue)
![PHP](https://img.shields.io/badge/PHP-8.5.4-blueviolet)

<p align="center">
  <img src="https://img.shields.io/github/v/release/BBRodriguez-22/snv4-font-builder">
  <img src="https://img.shields.io/github/downloads/BBRodriguez-22/snv4-font-builder/total">
  <img src="https://img.shields.io/badge/license-Non--Commercial-red">
</p>

A **local web-based tool** for converting sound fonts into **SNV4 format**.

Supports automatic conversion from:
- Proffie
- Xenopixel
- SNV4-style packs

Designed to remove the manual work of renaming, sorting, and testing sound files.

---

## ✨ Features

### 🔍 Smart Detection
- Automatically detects sound font roots
- Works with nested folder structures
- Supports Proffie, Xenopixel, and flat packs

### 🎧 Audio Preview
- Play sounds before building
- Review clashes, swings, drags, etc.
- Preview full output before conversion

### 🧠 Intelligent Mapping
- Auto-matches sounds using alias detection
- Handles variations like:
  - `clash`, `clsh`
  - `swing`, `swng`
  - `beginlock`, `bgnlock`

### ⚙️ Advanced Editing Mode
- Full preview of final build
- Reassign sounds between categories
- Delete unwanted sounds
- Duplicate sounds
- Drag & reorder sounds

### 🔄 Merge Decisions
- Handles duplicate sound sources (e.g. drag/begindrag)
- User can merge or select preferred sounds
- Audio playback available during decisions

### 📦 Build Output
- Outputs clean SNV4-ready folder
- Optional ZIP download
- Option to open output folder after build

### 🎨 UI Features
- Dark / Light theme toggle
- Clean, modern layout
- Advanced edit hidden by default for simplicity
- Clear conversion success screen

### 🧹 Job Management
- Reset session
- Clear temporary job folders
- Keeps working directory clean

---

## 📥 Installation

### 1. Download PHP

Required version:
php-8.5.4-nts-Win32-vs17-x64


Official page:  
https://www.php.net/downloads.php  

Direct download:  
https://downloads.php.net/~windows/releases/archives/php-8.5.4-nts-Win32-vs17-x64.zip  

---

### 2. Install PHP

- Extract the FULL ZIP
- Copy contents into the `/php` folder inside this project

⚠️ Do NOT copy only `php.exe`

---

### 3. Install Visual C++

Required:
- Microsoft Visual C++ Redistributable (VS17 / x64)

---

### 4. Run the tool

start-web.bat

This will:
- start the local PHP server
- open the browser UI

---

## 🚀 Usage

1. Upload your sound font ZIP
2. Select detected root
3. Review sounds (optional)
4. Use Advanced Edit if needed
5. Click **Build**
6. Output is saved to `/output`

---

## 📁 Output

- SNV4-ready folder
- Optional ZIP download
- Can open output folder directly

---

## 🔒 Security

- Runs **100% locally**
- No data is uploaded anywhere
- No external connections required

### Virus Scan

VirusTotal: **0 detections**

SHA256: c8b5f4dba88066ab80f7af37132f234b6ba243f0dcf12c91217095f1edcddb18


Verify:
https://www.virustotal.com/gui/file/c8b5f4dba88066ab80f7af37132f234b6ba243f0dcf12c91217095f1edcddb18/detection

---

## ⚠️ Important Notes

- This tool does **NOT include sound fonts**
- Users must own all fonts used
- Respect original creators and licenses

---

## 🛠 Supported Formats

| Source Type | Supported |
|------------|----------|
| Proffie    | ✅ |
| Xenopixel  | ✅ |
| SNV4       | ✅ |
| Mixed Packs| ✅ (best effort detection) |

---

## 📌 Known Limitations

- Very large ZIP files may upload slowly on older hardware
- Some uncommon naming conventions may require manual assignment
- Special sounds (e.g. lightningblock) are ignored unless manually assigned

---

## 🧭 Roadmap (Future Ideas)

- SNV4 config editor (sound + blade + ignition)
- Blade style preview
- Ignition preview
- Additional board support
- UI themes / styling options

---

## 🤝 Contributing / Feedback

Feel free to:
- open an Issue
- report bugs
- suggest features

---

## 📄 License

This project is licensed for **personal, non-commercial use only**.

See the LICENSE file for details.
