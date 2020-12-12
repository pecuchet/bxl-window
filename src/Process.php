<?php

namespace App;

use Carbon\Carbon;
use League\Flysystem\Filesystem;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Process extends Command
{
    /** @var string */
    const DIR_IN = 'input';

    /** @var string */
    const DIR_OUT = 'output';

    /** @var string */
    const META_FILE = 'images-meta.json';

    /** @var string */
    protected $basePath = '';

    /** @var ConsoleLogger */
    protected $logger;

    /** @var Filesystem */
    protected $filesystem;

    /** @var OutputInterface */
    protected $output;

    public function __construct(string $basePath, Filesystem $filesystem)
    {
        parent::__construct('process');

        $this->basePath = $basePath;

        $this->filesystem = $filesystem;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->logger = new ConsoleLogger($output, [
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL
        ]);

        $this->logger->log('info', 'Fetching remote meta data...');
        $remoteMeta = $this->getRemoteMetaData();
        $this->logger->log('info', 'Remote images: ' . count($remoteMeta));

        $this->emptyOutputDir();

        if ($meta = $this->processImages()) {
            $meta = $this->mergeMetaData($remoteMeta, $meta);
            $this->logger->log('info', 'New image count: ' . count($meta));
            $this->writeMetaDataFile($meta);
            $this->upload();
        }

        echo PHP_EOL;
        $this->logger->log('info', 'All done!');

        return 1;
    }

    protected function upload()
    {
        $this->logger->log('info', 'Uploading...');

        $images = $this->getAllImages('out');
        $progressBar = new ProgressBar($this->output, count($images));
        $progressBar->start();

        foreach ($images as $path) {
            $this->filesystem->put(basename($path), file_get_contents($path));
            $progressBar->advance();
        }

        $this->filesystem->put(
            static::META_FILE,
            file_get_contents($this->getPath('out', static::META_FILE))
        );

        $progressBar->advance();

        $progressBar->finish();
    }

    protected function processImages(): array
    {
        $this->logger->log('info', 'Processing...');

        $meta = [];

        foreach ($this->getAllImages('in') as $file) {
            if ($this->copyScaleImage($file)) {
                $meta[] = $this->getMetaData($file);
            }
        }

        return $meta;
    }

    protected function copyScaleImage(string $path): bool
    {
        $jpg = imagecreatefromjpeg($path);
        $width = imagesx($jpg) / 2;
        $jpg = imagescale($jpg, $width);
        return imagejpeg($jpg, $this->getPath('out', basename($path)));
    }

    protected function getMetaData($path): array
    {
        $exif = exif_read_data($path, 'ANY_TAG');
        $dateTimeOriginal = Carbon::parse($exif['DateTimeOriginal']);

        return [
            'name' => basename($path),
            'time' => $dateTimeOriginal->format('d M Y H:i:s')
        ];
    }

    protected function emptyOutputDir(): void
    {
        $this->logger->log('info', 'Emptying output dir...');

        array_map('unlink', array_filter((array)glob($this->getPath('out', '*'))));
    }

    protected function getPath(string $dir, string $append = ''): string
    {
        $dir = $dir === 'out' ? static::DIR_OUT : static::DIR_IN;

        return $this->basePath . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $append;
    }

    protected function getAllImages(string $dir, string $extension = 'JPG'): array
    {
        return (array)glob($this->getPath($dir, "*.$extension"));
    }

    protected function writeMetaDataFile(array $meta): bool
    {
        $this->logger->log('info', 'Writing meta data...');

        $meta = json_encode($meta);

        return (bool)file_put_contents($this->getPath('out', static::META_FILE), $meta);
    }

    protected function getRemoteMetaData(): array
    {
        return json_decode($this->filesystem->read(self::META_FILE), true);
    }

    protected function mergeMetaData($remoteMeta, array $meta)
    {
        $meta = array_merge($remoteMeta, $meta);

        usort($meta, function ($a, $b) {
            $a = Carbon::parse($a['time'])->timestamp;
            $b = Carbon::parse($b['time'])->timestamp;
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return $meta;
    }
}