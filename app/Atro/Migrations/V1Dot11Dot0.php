<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

namespace Atro\Migrations;

use Atro\Core\Exceptions\Error;
use Atro\Core\Migration\Base;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Espo\Core\Utils\Util;

class V1Dot11Dot0 extends Base
{
    public function getMigrationDateTime(): ?\DateTime
    {
        return new \DateTime('2024-05-21 12:00:00');
    }

    public function up(): void
    {
        $this->exec("ALTER TABLE storage ADD folder_id VARCHAR(24) DEFAULT NULL");
        $this->exec("CREATE INDEX IDX_STORAGE_FOLDER_ID ON storage (folder_id, deleted)");

        if ($this->isPgSQL()) {
            $this->exec("ALTER TABLE storage ADD sync_folders BOOLEAN DEFAULT 'false' NOT NULL");
            $this->exec(
                "CREATE TABLE file_folder_linker (id VARCHAR(24) NOT NULL, name VARCHAR(255) NOT NULL, deleted BOOLEAN DEFAULT 'false', parent_id VARCHAR(255) NOT NULL, folder_id VARCHAR(255) DEFAULT NULL, file_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))"
            );
            $this->exec("CREATE UNIQUE INDEX IDX_FILE_FOLDER_LINKER_UNIQUE_ITEM ON file_folder_linker (deleted, parent_id, name)");
            $this->exec("CREATE INDEX IDX_FILE_FOLDER_LINKER_FOLDER_IDX ON file_folder_linker (parent_id, folder_id)");
            $this->exec("CREATE INDEX IDX_FILE_FOLDER_LINKER_FILE_IDX ON file_folder_linker (parent_id, file_id)");
            $this->exec("CREATE UNIQUE INDEX UNIQ_8B5ADE4E162CB942EB3B4E33 ON file_folder_linker (folder_id, deleted)");
            $this->exec("CREATE UNIQUE INDEX UNIQ_8B5ADE4E93CB796CEB3B4E33 ON file_folder_linker (file_id, deleted)");
        } else {
            $this->exec("ALTER TABLE storage ADD sync_folders TINYINT(1) DEFAULT '0' NOT NULL");
            $this->exec(
                "CREATE TABLE file_folder_linker (id VARCHAR(24) NOT NULL, name VARCHAR(255) NOT NULL, deleted TINYINT(1) DEFAULT '0', parent_id VARCHAR(255) NOT NULL, folder_id VARCHAR(255) DEFAULT NULL, file_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX IDX_FILE_FOLDER_LINKER_UNIQUE_ITEM (deleted, parent_id, name), INDEX IDX_FILE_FOLDER_LINKER_FOLDER_IDX (parent_id, folder_id), INDEX IDX_FILE_FOLDER_LINKER_FILE_IDX (parent_id, file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB"
            );
            $this->exec("CREATE UNIQUE INDEX UNIQ_8B5ADE4E162CB942EB3B4E33 ON file_folder_linker (folder_id, deleted)");
            $this->exec("CREATE UNIQUE INDEX UNIQ_8B5ADE4E93CB796CEB3B4E33 ON file_folder_linker (file_id, deleted)");
        }

        $this->getConnection()->createQueryBuilder()->delete('file_folder_linker')->executeQuery();

        $this->createFoldersItems();
        $this->createFilesItems();

        try {
            $records = $this->getConnection()->createQueryBuilder()
                ->select('*')
                ->from('folder_storage')
                ->where('deleted=:false')
                ->setParameter('false', false, ParameterType::BOOLEAN)
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            $records = [];
        }

        foreach ($records as $record) {
            $this->getConnection()->createQueryBuilder()
                ->update('storage')
                ->set('folder_id', ':folderId')
                ->where('id=:storageId')
                ->setParameter('folderId', $record['folder_id'])
                ->setParameter('storageId', $record['storage_id'])
                ->executeQuery();
        }

        $this->exec("DROP TABLE folder_storage");

        $this->updateComposer('atrocore/core', '^1.11.0');
    }

    public function down(): void
    {
        throw new Error('Downgrade is prohibited.');
    }

    public function createFoldersItems(): void
    {
        $records = $this->getConnection()->createQueryBuilder()
            ->select('f.*, h.parent_id')
            ->from('folder', 'f')
            ->leftJoin('f', 'folder_hierarchy', 'h', 'f.id=h.entity_id')
            ->where('f.deleted=:false')
            ->andWhere('f.deleted=:false')
            ->setParameter('false', false, ParameterType::BOOLEAN)
            ->fetchAllAssociative();

        foreach ($records as $record) {
            try {
                $this->getConnection()->createQueryBuilder()
                    ->insert('file_folder_linker')
                    ->setValue('id', ':id')
                    ->setValue('name', ':name')
                    ->setValue('parent_id', ':parentId')
                    ->setValue('folder_id', ':folderId')
                    ->setParameter('id', Util::generateId())
                    ->setParameter('name', (string)$record['name'])
                    ->setParameter('parentId', (string)$record['parent_id'])
                    ->setParameter('folderId', (string)$record['id'])
                    ->executeQuery();
            } catch (UniqueConstraintViolationException $e) {
                $newName = $record['name'] . ' (' . Util::generateId() . ')';

                $this->getConnection()->createQueryBuilder()
                    ->update('folder')
                    ->set('name', ':name')
                    ->where('id=:id')
                    ->setParameter('id', $record['id'])
                    ->setParameter('name', $newName)
                    ->executeQuery();

                try {
                    $this->getConnection()->createQueryBuilder()
                        ->insert('file_folder_linker')
                        ->setValue('id', ':id')
                        ->setValue('name', ':name')
                        ->setValue('parent_id', ':parentId')
                        ->setValue('folder_id', ':folderId')
                        ->setParameter('id', Util::generateId())
                        ->setParameter('name', $newName)
                        ->setParameter('parentId', (string)$record['parent_id'])
                        ->setParameter('folderId', (string)$record['id'])
                        ->executeQuery();
                } catch (\Throwable $e) {

                }
            }
        }
    }

    public function createFilesItems(): void
    {
        $records = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from('file')
            ->where('deleted=:false')
            ->setParameter('false', false, ParameterType::BOOLEAN)
            ->fetchAllAssociative();

        foreach ($records as $record) {
            try {
                $this->getConnection()->createQueryBuilder()
                    ->insert('file_folder_linker')
                    ->setValue('id', ':id')
                    ->setValue('name', ':name')
                    ->setValue('parent_id', ':parentId')
                    ->setValue('file_id', ':fileId')
                    ->setParameter('id', Util::generateId())
                    ->setParameter('name', (string)$record['name'])
                    ->setParameter('parentId', (string)$record['folder_id'])
                    ->setParameter('fileId', (string)$record['id'])
                    ->executeQuery();
            } catch (UniqueConstraintViolationException $e) {
                $parts = explode('.', $record['name']);
                $ext = array_pop($parts);
                $newName = implode('.', $parts) . ' (' . Util::generateId() . ').' . $ext;

                $this->getConnection()->createQueryBuilder()
                    ->update('file')
                    ->set('name', ':name')
                    ->where('id=:id')
                    ->setParameter('id', $record['id'])
                    ->setParameter('name', $newName)
                    ->executeQuery();

                try {
                    $this->getConnection()->createQueryBuilder()
                        ->insert('file_folder_linker')
                        ->setValue('id', ':id')
                        ->setValue('name', ':name')
                        ->setValue('parent_id', ':parentId')
                        ->setValue('file_id', ':fileId')
                        ->setParameter('id', Util::generateId())
                        ->setParameter('name', $newName)
                        ->setParameter('parentId', (string)$record['folder_id'])
                        ->setParameter('fileId', (string)$record['id'])
                        ->executeQuery();
                } catch (\Throwable $e) {

                }
            }
        }
    }

    protected function exec(string $sql): void
    {
        try {
            $this->getPDO()->exec($sql);
        } catch (\Throwable $e) {
        }
    }
}
