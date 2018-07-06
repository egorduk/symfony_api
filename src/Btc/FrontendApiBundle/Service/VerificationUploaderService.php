<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\Attachment;
use Btc\FrontendApiBundle\Classes\RestFile;
use Btc\FrontendApiBundle\Exception\Rest\InvalidFileException;
use Btc\FrontendApiBundle\Exception\Rest\UnknownErrorException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator;

class VerificationUploaderService
{
    private $uploadDir = '';
    private $em;
    private $validator;

    public function __construct(EntityManager $em, $uploadDir, Validator\ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->uploadDir = rtrim($uploadDir, '/');

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir);
        }
    }

    /**
     * @param RestFile $file
     *
     * @return Attachment
     */
    public function uploadFile(RestFile $file)
    {
        $content = base64_decode($file->getContent());
        $name = $file->getName().uniqid('_file', true);
        $filePath = $this->uploadDir.DIRECTORY_SEPARATOR.$name;

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new UnknownErrorException();
        };

        $mimeType = $file->getMimeType($filePath);

        $mimeTypesAllowed = ['image/gif', 'image/jpeg', 'image/png', 'image/pjpeg', 'image/bmp', 'image/webp'];

        if (!in_array($mimeType, $mimeTypesAllowed)) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            throw new InvalidFileException();
        }

        $path = $this->uploadDir.DIRECTORY_SEPARATOR.$name;

        $uploadedFile = new UploadedFile($path, $name, $mimeType, filesize($path));

        $attachment = new Attachment();
        $attachment->setFile($uploadedFile);
        $attachment->setName($file->getName());
        $attachment->setOriginalName($name);
        $attachment->setExtension($mimeType);

        $this->em->persist($attachment);

        return $attachment;
    }
}
