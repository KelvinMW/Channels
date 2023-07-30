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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Channels\Domain\CategoryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Channels/categories_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $channelsCategoryID = $_GET['channelsCategoryID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Categories'), 'categories_manage.php')
        ->add(__m('Edit Category'));

    if (empty($channelsCategoryID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(CategoryGateway::class)->getByID($channelsCategoryID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module').'/categories_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('channelsCategoryID', $channelsCategoryID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $options = array(
        "None" => __m('None'),
        "View" => __m('View'),
        "Post" => __m('Post')
    );
    $row = $form->addRow();
        $row->addLabel('staffAccess', __m('Staff Access'));
        $row->addSelect('staffAccess')->fromArray($options)->required();

    $row = $form->addRow();
        $row->addLabel('studentAccess', __m('Student Access'));
        $row->addSelect('studentAccess')->fromArray($options)->required();

    $row = $form->addRow();
        $row->addLabel('parentAccess', __m('Parent Access'));
        $row->addSelect('parentAccess')->fromArray($options)->required();

    $row = $form->addRow();
        $row->addLabel('otherAccess', __m('Other Access'));
        $row->addSelect('otherAccess')->fromArray($options)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
