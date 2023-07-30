<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//This file describes the module, including database tables

//Basic variables
$name = 'Channels';
$description = 'Channels allows school to create communication links to community members.';
$entryURL = 'channels.php';
$type = 'Additional';
$category = 'Other';
$version = '0.1.10';
$author = 'Kelvin Maina';
$url = 'https://github.com/KelvinMW';

//Module tables
$moduleTables[] = "CREATE TABLE `channelsPost` (
  `channelsPostID` INT(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` INT(3) unsigned zerofill NOT NULL,
  `gibbonPersonID` INT(10) unsigned zerofill NOT NULL,
  `gibbonCourseClassID` varchar(100) DEFAULT NULL,  
  `post` TEXT,
  `channelsCategoryIDList` VARCHAR(255) DEFAULT NULL,
  `timestamp` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`channelsPostID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `channelsCategory` (
  `channelsCategoryID` INT(3) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `active` ENUM('Y','N') NOT NULL DEFAULT 'Y',
  `sequenceNumber` INT(3) NULL,
  `staffAccess` ENUM('None','View','Post') NOT NULL DEFAULT 'None',
  `studentAccess` ENUM('None','View','Post') NOT NULL DEFAULT 'None',
  `parentAccess` ENUM('None','View','Post') NOT NULL DEFAULT 'None',
  `otherAccess` ENUM('None','View','Post') NOT NULL DEFAULT 'None',
  PRIMARY KEY (`channelsCategoryID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `channelsCategoryViewed` (
    `channelsCategoryViewedID` INT(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
    `gibbonPersonID` INT(10) unsigned zerofill NOT NULL,
    `channelsCategoryID` INT(3) unsigned zerofill NOT NULL,
    `timestamp` TIMESTAMP NULL,
    PRIMARY KEY (`channelsCategoryViewedID`),
    UNIQUE KEY `lastViewed` (`gibbonPersonID`, `channelsCategoryID`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `channelsPostTag` (
  `channelsPostTagID` INT(11) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `channelsPostID` INT(10) unsigned zerofill DEFAULT NULL,
  `tag` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`channelsPostTagID`),
  UNIQUE KEY `channelsPostID` (`channelsPostID`, `tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$moduleTables[] = "CREATE TABLE `channelsPostAttachment` (
  `channelsPostAttachmentID` INT(11) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `channelsPostID` INT(10) unsigned zerofill DEFAULT NULL,
  `attachment` VARCHAR(255) NOT NULL DEFAULT '',
  `thumbnail` VARCHAR(255) NULL,
  `type` ENUM('Image', 'Video') NOT NULL DEFAULT 'Image',
  PRIMARY KEY (`channelsPostAttachmentID`),
  KEY `channelsPostID` (`channelsPostID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";


//Settings
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Channels', 'postLength', 'Post Length', 'Maximum number of characters in a post.', '280');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Channels', 'maxImageSize', 'Max Image Size', 'Maximum image size in pixels. Larger images will be scaled down.', '1400');";
$gibbonSetting[] = "INSERT INTO `gibbonSetting` (`gibbonSettingID` ,`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES (NULL , 'Channels', 'showPreviousYear', 'Show Previous Year', 'Should posts from the immediately previous year be displayed in Channels?', 'N');";

//Action rows
$actionRows[] = [
    'name'                      => 'View Channels',
    'precedence'                => '0',
    'category'                  => 'Channels',
    'description'               => 'View the channels of shared posts.',
    'URLList'                   => 'channels.php',
    'entryURL'                  => 'channels.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Manage Categories',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Create, edit and delete channels categories.',
    'URLList'                   => 'categories_manage.php,categories_manage_add.php,categories_manage_edit.php,categories_manage_delete.php',
    'entryURL'                  => 'categories_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Posts_all',
    'precedence'                => '1',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage all posts within the system.',
    'URLList'                   => 'posts_manage.php,posts_manage_add.php,posts_manage_edit.php,posts_manage_delete.php',
    'entryURL'                  => 'posts_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

$actionRows[] = [
    'name'                      => 'Manage Posts_my',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage their own posts.',
    'URLList'                   => 'posts_manage.php,posts_manage_add.php,posts_manage_edit.php,posts_manage_delete.php',
    'entryURL'                  => 'posts_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'Y',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'Y',
    'categoryPermissionOther'   => 'Y',
];

$actionRows[] = [
    'name'                      => 'Channels Settings',
    'precedence'                => '0',
    'category'                  => 'Manage',
    'description'               => 'Allows a user to manage settings for the Channels Module.',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];
