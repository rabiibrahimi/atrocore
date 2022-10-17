<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

namespace Treo\Core\Migration;

use Doctrine\DBAL\Connection;
use PDO;
use Espo\Core\Utils\Config;

class Base
{
    private Connection $connection;

    private Config $config;

    private PDO $pdo;

    public function __construct(Connection $connection, Config $config, PDO $pdo)
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->pdo = $pdo;
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }

    protected function renderLine(string $message, bool $break = true)
    {
        $result = date('d.m.Y H:i:s') . ' | ' . $message;
        if ($break) {
            $result .= PHP_EOL;
        }

        echo $result;
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return PDO
     * @deprecated Method is deprecated, please use getConnection instead.
     */
    protected function getPDO(): PDO
    {
        return $this->pdo;
    }
}
