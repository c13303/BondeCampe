# BondeCompe
music sharing script

example : http://bondecampe.5tfu.org

If you have a FTP connexion, this script aim to be the simplest way to share music albums. 
Features : 

- No database required
- Display of albums as a page
- HTML5 mp3 player
- Download of single tracks & full album archive
- Auto-create zip archive of full albums
- Folders (albums, demos ..)
- Log visitor activity (no google analytics ...) as .csv file


----------------------------------

Install : 

Copy The script on a php server, no database required

Modifiy variables such as $artist in index.php as configuration.


----------------------------------

Add an album :

-Just create a folder in /audio named with number-title, example  : /0-example

-Inside the folder, name mp3 like this number-title.mp3, example : 1-title.mp3, 2-title.mp3 etc ...

-Rename the cover art (exact name) : cover.jpg

-Put any .html OR .txt file with the text to be displayed, example : about.html


Full example of an album architecture: 

0000-Album 1 Title Folder
    1-title.mp3
    2-title.mp3
    3-title.mp3
    cover.jpg (required)
    about.html
    flac.rar (optionnal)
    .config (optionnal)




----------------------------------
Zip archive : 

When the archive is requested, if the file doesn't exist yet, zip.php create a zip archive in /archive with all the content of the folder.

The /archive folder should be writable.

If later the album folder (in /audio) has somehow changed (mp3 replaced for example), just delete the archive in /archive and the .zip will be regenerated on next request.



----------------------------------

Include a lossless files archive  :

Put a .rar file with files inside it and the link will be created



----------------------------------
Description HTML

Put any .html file in the folder



----------------------------------

Config 

Put a .config file into folder
Write option inside :
NOZIP : disabled zip/rar download on the page







author : Charles Torris

contact : erreure@gmail.com
