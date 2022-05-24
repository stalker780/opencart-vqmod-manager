# OpenCart 4 VQMod Manager

New long awaited OpenCart 4.0 is coming without familiar OCMod embedded.
It will use Event system instead.

This extension is a solution for those who still wish to create own extensions modifications without editing core files.
It allows to include VQMod XML files inside OC4 extension zip files and install them on extension installation.

Extension was developed with plug and play in mind. It does not require you to download VQMod, upload and install it manually.
All this can be done inside Module settings using admin interface.

VQMod GitHub: https://github.com/vqmod/vqmod

VQMod Wiki: https://github.com/vqmod/vqmod/wiki/Examples

Demo website will be available soon.

## Extension features:
- includes VQMod installer right from your admin panel
- allows to manage/edit/create VQMod XML files
- filter VQMods by filename/name/author/contents/status
- modifies extension installer to allow "upload/vqmod/xml/*.xml" files inside extension zip
- modified files list
- view logs
- modification dumps
- cache clear
- uses CodeMirror editor for XML syntax highlight
- XML structure validation

## OCMod structure with embedded VQMod XML files:
```
my_extension_package.ocmod.zip
|-my_extension_code1/
|-my_extension_code1/admin/
|-my_extension_code1/catalog/
|-my_extension_code1/system/
|-my_extension_code1/vqmod/xml/my_vqmod_extension.xml
|-my_extension_code1/install.json

|-my_extension_code2/
|-my_extension_code2/admin/
|-my_extension_code2/catalog/
|-my_extension_code2/system/
|-my_extension_code2/vqmod/xml/my_vqmod_extension.xml
|-my_extension_code2/install.json
```

## Installation steps:
1. Rename downloaded file to clicker_vqmod_manager.ocmod.zip
2. Open Extensions->Installer
3. Upload clicker_vqmod_manager.ocmod.zip
4. Find uploaded extension in a list and click Install
5. Open Extensions->Extensions->Modules
6. Find VQModManager module and click install
7. Now open module and follow VQMod installation steps
8. Ready to use

Extension is free for use.
