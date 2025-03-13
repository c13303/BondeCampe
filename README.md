# BondeCompe

A simple, no-database PHP script for easily sharing music albums on your website.

**Demo:** [http://bondecampe.5tfu.org](http://bondecampe.5tfu.org)

---

## Features

- No database required
- Automatically generates an album page with an HTML5 MP3 player
- Allows individual track playback and downloads
- Automatically creates downloadable ZIP archives for entire albums
- Supports additional file types (e.g., lossless formats like .rar)

## Installation

1. Copy the entire script onto your PHP-enabled web server.
2. Ensure your server supports PHP (no database required).
3. Adjust settings in `index.php` and `config/config.php` as needed (e.g., set `$artist`, `$baseurl`).

## Adding Albums

To add a new album:

- Create a new folder inside `/audio` following this naming convention:
  ```
  /audio/number-album-title
  ```
  Example: `/audio/0-my-album`

- Within this folder:
  - Name each MP3 file with the format:
    ```
    number-title.mp3
    ```
    Example: `1-track-one.mp3`, `2-track-two.mp3`, etc.
  
  - Add a cover image named exactly:
    ```
    cover.jpg
    ```

  - Optionally, include:
    - An HTML or text file (`about.html` or `about.txt`) for additional album description.
    - A `.rar` file containing lossless audio formats.
    - A `.config` file for custom settings, such as disabling zip downloads:
      ```
      NOZIP
      ```

Example album structure:
```
/audio/0-my-album
├── 1-track-one.mp3
├── 2-track-two.mp3
├── cover.jpg (required)
├── about.html (optional)
├── flac.rar (optional)
└── .config (optional)
```

## Automatic ZIP Creation

- Zip archives are automatically created on-demand the first time an album zip is downloaded and stored in `/archive`.
- To refresh a zip file (if album content changes), delete the existing zip in `/archive`. The next download request will regenerate it automatically.
- Ensure the `/archive` folder is writable by your server.

## Usage

- Access your script via your configured URL. Albums are automatically listed.
- Visitors can stream MP3 tracks directly on the page or download individual tracks and complete albums as zip archives.

## Customizing

Edit configuration variables (e.g., artist name, base URL) in:
```
config/config.php
```


---

**Author:** Charles Torris  
**Contact:** [erreure@gmail.com](mailto:erreure@gmail.com)

