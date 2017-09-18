<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version   OXID eShop CE
 */

namespace OxidEsales\EshopCommunity\Core;

/**
 * Class used for counting users depending on given attributes.
 */
class UserCounter
{
    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     *
     * @return int
     */
    public function getAdminCount()
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sQuery = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user'";

        return (int) $oDb->getOne($sQuery);
    }

    /**
     * Returns count of admins (mall and subshops). Only counts active admins.
     *
     * @return int
     */
    public function getActiveAdminCount()
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

        $sQuery = "SELECT COUNT(1) FROM oxuser WHERE oxrights != 'user' AND oxactive = 1 ";

        return (int) $oDb->getOne($sQuery);
    }
}