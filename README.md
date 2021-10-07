# OpenCart 4 VQMod Manager

New long awaited OpenCart 4.0 is coming without familiar OCMod embedded.
It will use Event system instead.

This extension is a solutions for those who still wish to create own extensions modifications without editing core files.
It allows to include VQMod XML files inside OC4 extension zip files and install them on extension installation.

Extension was developed with plug and play in mind. It does not require you to download VQMod, upload and install it manually. 
All this can be done inside Module settings using admin interface.

VQMod GitHub: https://github.com/vqmod/vqmod

VQMod Wiki: https://github.com/vqmod/vqmod/wiki/Examples

Demo website will be available soon.

Extension features:
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

Installation steps:
1. Rename downloaded file to clicker_vqmod_manager.ocmod.zip
1. Open Extensions->Installer
2. Upload clicker_vqmod_manager.ocmod.zip
3. Find uploaded extension in a list and click Install
4. Open Extensions->Extensions->Modules
5. Find VQModManager module and click install
6. Now open module and follow VQMod installation steps
7. Ready to use

Extension is free for use.

Extension will be updated as OC4 evolves. Since it is still beta and may have significant core changes before release.
