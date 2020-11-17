<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix paths to static icons based on filetype
 */
class Version20200823164701 extends AbstractMigration
{
    private const TYPES = [
        '3g2', '3ga', '3gp', '7z', 'aa', 'aac', 'ac', 'accdb', 'accdt', 'adn', 'ai', 'aif', 'aifc', 'aiff', 'ait', 'amr', 'ani', 'apk', 'app', 'applescript', 'asax', 'asc', 'ascx', 'asf', 'ash', 'ashx', 'asmx', 'asp', 'aspx', 'asx', 'au', 'aup', 'avi', 'axd', 'aze', 'bak', 'bash', 'bat', 'bin', 'blank', 'bmp', 'bowerrc', 'bpg', 'browser', 'bz2', 'c', 'cab', 'cad', 'caf', 'cal', 'cd', 'cer', 'cfg', 'cfm', 'cfml', 'cgi', 'class', 'cmd', 'codeki', 'coffee', 'coffeelintignor', 'com', 'compil', 'conf', 'config', 'cpp', 'cptx', 'cr2', 'crdownloa', 'crt', 'crypt', 'cs', 'csh', 'cson', 'csproj', 'css', 'csv', 'cue', 'dat', 'db', 'dbf', 'deb', 'dgn', 'dist', 'diz', 'dll', 'dmg', 'dng', 'doc', 'docb', 'docm', 'docx', 'dot', 'dotm', 'dotx', 'download', 'dpj', 'store', 'dtd', 'dwg', 'dxf', 'editorconfig', 'el', 'enc', 'eot', 'eps', 'epub', 'eslintignore', 'exe', 'f4v', 'fax', 'fb2', 'fla', 'flac', 'flv', 'folder', 'gadget', 'gdp', 'gem', 'gif', 'gitattributes', 'gitignore', 'go', 'gpg', 'gz', 'h', 'handlebars', 'hbs', 'heic', 'hs', 'hsl', 'htm', 'html', 'ibooks', 'icns', 'ico', 'ics', 'idx', 'iff', 'ifo', 'image', 'img', 'in', 'indd', 'inf', 'ini', 'iso', 'j2', 'jar', 'java', 'jpe', 'jpeg', 'jpg', 'js', 'json', 'jsp', 'jsx', 'key', 'kf8', 'kmk', 'ksh', 'kup', 'less', 'lex', 'licx', 'lisp', 'lit', 'lnk', 'lock', 'log', 'lua', 'm', 'm2v', 'm3u', 'm3u8', 'm4', 'm4a', 'm4r', 'm4v', 'map', 'master', 'mc', 'md', 'mdb', 'mdf', 'me', 'mi', 'mid', 'midi', 'mk', 'mkv', 'mm', 'mo', 'mobi', 'mod', 'mov', 'mp2', 'mp3', 'mp4', 'mpa', 'mpd', 'mpe', 'mpeg', 'mpg', 'mpga', 'mpp', 'mpt', 'msi', 'msu', 'nef', 'nes', 'nfo', 'nix', 'npmignore', 'odb', 'ods', 'odt', 'ogg', 'ogv', 'ost', 'otf', 'ott', 'ova', 'ovf', 'p12', 'p7b', 'pages', 'part', 'pcd', 'pdb', 'pdf', 'pem', 'pfx', 'pgp', 'ph', 'phar', 'php', 'pkg', 'pl', 'plist', 'pm', 'png', 'po', 'pom', 'pot', 'potx', 'pps', 'ppsx', 'ppt', 'pptm', 'pptx', 'prop', 'ps', 'ps1', 'psd', 'psp', 'pst', 'pub', 'py', 'pyc', 'qt', 'ra', 'ram', 'rar', 'raw', 'rb', 'rdf', 'resx', 'retry', 'rm', 'rom', 'rpm', 'rsa', 'rss', 'rtf', 'ru', 'rub', 'sass', 'scss', 'sdf', 'sed', 'sh', 'sitemap', 'skin', 'sldm', 'sldx', 'sln', 'sol', 'sql', 'sqlite', 'step', 'stl', 'svg', 'swd', 'swf', 'swift', 'sys', 'tar', 'tcsh', 'tex', 'tfignore', 'tga', 'tgz', 'tif', 'tiff', 'tmp', 'torrent', 'ts', 'tsv', 'ttf', 'twig', 'txt', 'udf', 'vb', 'vbproj', 'vbs', 'vcd', 'vcs', 'vdi', 'vdx', 'vmdk', 'vob', 'vscodeignore', 'vsd', 'vss', 'vst', 'vsx', 'vtx', 'war', 'wav', 'wbk', 'webinfo', 'webm', 'webp', 'wma', 'wmf', 'wmv', 'woff', 'woff2', 'wps', 'wsf', 'xaml', 'xcf', 'xlm', 'xls', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx', 'xml', 'xpi', 'xps', 'xrb', 'xsd', 'xsl', 'xspf', 'xz', 'yaml', 'yml', 'z', 'zip', 'zsh'
    ];

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Fix paths to static icons based on filetype';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on "postgresql".');

        foreach (self::TYPES as $type) {
            $this->addSql(sprintf(
                "UPDATE neos_media_domain_model_thumbnail SET staticresource = 'resource://Neos.Media/Public/IconSets/vivid/%s.svg' WHERE (staticresource = 'resource://Neos.Media/Public/Icons/512px/%s.png')",
                $type,
                $type
            ));
        }
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws DBALException
     * @throws AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on "postgresql".');

        foreach (self::TYPES as $type) {
            $this->addSql(sprintf(
                "UPDATE neos_media_domain_model_thumbnail SET staticresource = 'resource://Neos.Media/Public/IconSets/vivid/%s.png' WHERE (staticresource = 'resource://Neos.Media/Public/Icons/512px/%s.svg')",
                $type,
                $type
            ));
        }
    }
}
