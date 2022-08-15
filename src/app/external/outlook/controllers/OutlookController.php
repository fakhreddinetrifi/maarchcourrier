<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief   Outlook Controller
 * @author  dev@maarch.org
 */

namespace Outlook\controllers;

use Configuration\models\ConfigurationModel;
use Doctype\models\DoctypeModel;
use Group\controllers\PrivilegeController;
use IndexingModel\models\IndexingModelModel;
use Respect\Validation\Validator;
use Slim\Http\Request;
use Slim\Http\Response;
use SrcCore\controllers\LanguageController;
use SrcCore\models\CoreConfigModel;
use SrcCore\models\PasswordModel;
use Status\models\StatusModel;
use User\models\UserModel;

class OutlookController
{
    public function generateManifest(Request $request, Response $response)
    {
        $config = CoreConfigModel::getJsonLoaded(['path' => 'apps/maarch_entreprise/xml/config.json']);
        $appName = $config['config']['applicationName'];
        $maarchUrl = $config['config']['maarchUrl'];

        if (strpos($maarchUrl, 'https://') === false) {
            return $response->withStatus(400)->withJson(['errors' => 'You cannot use the Outlook plugin because maarchUrl is not using https', 'lang' => 'addinOutlookUnavailable']);
        }

        $maarchUrl = str_replace('//', '/', $maarchUrl);
        $maarchUrl = str_replace('https:/', 'https://', $maarchUrl);

        $path = CoreConfigModel::getConfigPath();
        $hashedPath = md5($path);

        $uuid = substr_replace($hashedPath, '-', 8, 0);
        $uuid = substr_replace($uuid, '-', 13, 0);
        $uuid = substr_replace($uuid, '-', 18, 0);
        $uuid = substr_replace($uuid, '-', 23, 0);

        $appDomain = str_replace(CoreConfigModel::getCustomId(), '', $maarchUrl);
        $appDomain = str_replace('//', '/', $appDomain);


        $data = [
            'config.applicationName' => $appName,
            'config.instanceUrl'     => $maarchUrl,
            'config.applicationUrl'  => $appDomain,
            'config.applicationUuid' => $uuid
        ];

        $language = LanguageController::getLanguage(['language' => $config['config']['lang']]);
        foreach ($language['lang'] as $key => $lang) {
            $data['lang.' . $key] = $lang;
        }

        $manifestTemplate = file_get_contents('plugins/addin-outlook/src/config/manifest.xml.default');

        $newContent = $manifestTemplate;
        foreach ($data as $key => $value) {
            $newContent = str_replace('{' . $key . '}', $value, $newContent);
        }

        $response->write($newContent);
        $response = $response->withAddedHeader('Content-Disposition', 'attachment; filename="manifest.xml"');
        return $response->withHeader('Content-Type', 'application/xml');
    }

    public function getConfiguration(Request $request, Response $response)
    {
        $configuration = ConfigurationModel::getByPrivilege(['privilege' => 'admin_addin_outlook']);

        $configuration['value'] = json_decode($configuration['value'], true);

        if (!empty($configuration['value']['indexingModelId'])) {
            $model = IndexingModelModel::getById(['id' => $configuration['value']['indexingModelId'], 'select' => ['label']]);
            if (!empty($model)) {
                $configuration['value']['indexingModelLabel'] = $model['label'];
            }
        }

        if (!empty($configuration['value']['typeId'])) {
            $type = DoctypeModel::getById(['id' => $configuration['value']['typeId'], 'select' => ['description']]);
            if (!empty($type)) {
                $configuration['value']['typeLabel'] = $type['description'];
            }
        }

        if (!empty($configuration['value']['statusId'])) {
            $status = StatusModel::getByIdentifier(['identifier' => $configuration['value']['statusId'], 'select' => ['label_status', 'id']]);
            if (!empty($status)) {
                $configuration['value']['statusLabel'] = $status[0]['label_status'];
                $configuration['value']['status'] = $status[0]['id'];
            }
        }

        return $response->withJson(['configuration' => $configuration['value']]);
    }

    public function saveConfiguration(Request $request, Response $response)
    {
        if (!PrivilegeController::hasPrivilege(['privilegeId' => 'admin_parameters', 'userId' => $GLOBALS['id']])) {
            return $response->withStatus(403)->withJson(['errors' => 'Service forbidden']);
        }

        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->intVal()->validate($body['indexingModelId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body indexingModelId is empty or not an integer']);
        } elseif (!Validator::notEmpty()->intVal()->validate($body['typeId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body typeId is empty or not an integer']);
        } elseif (!Validator::notEmpty()->intVal()->validate($body['statusId'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body statusId is empty or not an integer']);
        }

        $model = IndexingModelModel::getById(['id' => $body['indexingModelId'], 'select' => ['master']]);
        if (empty($model)) {
            return $response->withStatus(400)->withJson(['errors' => 'Indexing model does not exist']);
        } elseif (!empty($model['master'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Indexing model is not a public model']);
        }

        $type = DoctypeModel::getById(['id' => $body['typeId'], 'select' => [1]]);
        if (empty($type)) {
            return $response->withStatus(400)->withJson(['errors' => 'Document type does not exist']);
        }

        $status = StatusModel::getByIdentifier(['identifier' => $body['statusId'], 'select' => [1]]);
        if (empty($status)) {
            return $response->withStatus(400)->withJson(['errors' => 'Status does not exist']);
        }

        $data = ['indexingModelId' => $body['indexingModelId'], 'typeId' => $body['typeId'], 'statusId' => $body['statusId']];
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (empty(ConfigurationModel::getByPrivilege(['privilege' => 'admin_addin_outlook', 'select' => [1]]))) {
            ConfigurationModel::create(['value' => $data, 'privilege' => 'admin_addin_outlook']);
        } else {
            ConfigurationModel::update(['set' => ['value' => $data], 'where' => ['privilege = ?'], 'data' => ['admin_addin_outlook']]);
        }

        return $response->withStatus(204);
    }

    public function saveOutlookPassword(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        if (!Validator::notEmpty()->stringType()->validate($body['outlookPassword'])) {
            return $response->withStatus(400)->withJson(['errors' => 'Body outlookPassword is empty or not an integer']);
        }

        $preferences = UserModel::getById(['id' => $GLOBALS['id'], 'select' => ['preferences']]);
        $preferences = json_decode($preferences, true);

        $preferences['outlookPassword'] = PasswordModel::encrypt(['password' => $body['outlookPassword']]);

        UserModel::update(['set' => ['preferences' => json_encode($preferences)], 'where' => ['id = ?'], 'data' => [$GLOBALS['id']]]);

        return $response->withStatus(204);
    }
}
