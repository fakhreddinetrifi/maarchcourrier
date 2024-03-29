<?php

/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.
*
*/

use PHPUnit\Framework\TestCase;

class FolderControllerTest extends TestCase
{
    private static $id = null;
    private static $idSub = null;
    private static $idSubSub = null;
    private static $idMoved = null;

    private static $idFirstResource = null;
    private static $idSecondResource = null;
    private static $idThirdResource = null;


    public function testCreate()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $body = [
            'label'      => 'Mon premier dossier'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        self::$id = $responseBody->folder;

        $this->assertIsInt(self::$id);

        // Create SubFolder
        $body = [
            'label'     => 'Mon deuxieme dossier',
            'parent_id' => self::$id
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertIsInt($responseBody->folder);
        self::$idSub = $responseBody->folder;

        //  Error

        $body = [
            'label' => ''
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body label is empty or not a string', $responseBody->errors);


        $body = [
            'label' => 'Test',
            'parent_id' => 'wrong format'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Body parent_id is not an integer', $responseBody->errors);


        $body = [
            'label' => 'Test',
            'parent_id' => self::$id * 1000
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Parent Folder not found or out of your perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdate()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $body = [
            'label' => 'Mon premier dossier renomme',
            'parent_id'  => 0
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(200, $response->getStatusCode());

        //ERROR
        $body = [
            'label' => 'Mon deuxieme dossier renomme 2',
            'parent_id'  => 999999
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('parent_id does not exist or Id is a parent of parent_id', $responseBody->errors);

        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Query id is empty or not an integer', $responseBody->errors);


        $body = [
            'label' => '',
            'parent_id'  => 999999
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body label is empty or not a string', $responseBody->errors);

        $body = [
            'label' => 'TEST',
            'parent_id'  => 'wrong format'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body parent_id is not an integer', $responseBody->errors);

        $body = [
            'label' => 'TEST',
            'parent_id'  => self::$id
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Parent_id and id can not be the same', $responseBody->errors);


        $body = [
            'label' => 'TEST'
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testSharing()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        //ERROR
        $body = [

        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Query id is empty or not an integer', $responseBody->errors);

        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Body public is empty or not a boolean', $responseBody->errors);

        $body = [
            'public' => true
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Body sharing/entities does not exists', $responseBody->errors);

        //  Success
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        // Test bblier cannot get folder
        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody());
        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);


        // share folder with entity 13, which bblier is part of
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
            'public' => true,
            'sharing' => [
                'entities' => [
                    [
                        'entity_id' => 13,
                        'edition' => true
                    ]
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);

        $this->assertSame(204, $response->getStatusCode());

        // check that bblier can now get folder
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertNotEmpty($responseBody['folder']);

        $this->assertNotEmpty($responseBody['folder']['sharing']['entities']);
        $this->assertSame(13, $responseBody['folder']['sharing']['entities'][0]['entity_id']);
        $this->assertSame(true, $responseBody['folder']['sharing']['entities'][0]['edition']);
        $this->assertSame(true, $responseBody['folder']['sharing']['entities'][0]['canDelete']);


        // Set different sharing for sub-folder
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
            'public' => true,
            'sharing' => [
                'entities' => [
                    [
                        'entity_id' => 13,
                        'edition' => false
                    ],
                    [
                        'keyword' => 'ALL_KEYWORD',
                        'edition' => false
                    ]
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$idSub]);

        $this->assertSame(204, $response->getStatusCode());

        // test that bblier can get sub-folder, but that he cannot edit/delete it
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$idSub]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertNotEmpty($responseBody['folder']);

        $this->assertNotEmpty($responseBody['folder']['sharing']['entities']);
        $this->assertSame(13, $responseBody['folder']['sharing']['entities'][0]['entity_id']);
        $this->assertSame(false, $responseBody['folder']['sharing']['entities'][0]['edition']);
        $this->assertSame(false, $responseBody['folder']['sharing']['entities'][0]['canDelete']);

        // check that bblier cannot share sub-folder
        $body = [
            'public' => true,
            'sharing' => [
                'entities' => [
                    [
                        'entity_id' => 14,
                        'edition' => true
                    ]
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$idSub]);

        $this->assertSame(400, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Cannot share/unshare folder because at least one folder is out of your perimeter', $responseBody['errors']);

        $response     = $folderController->pinFolder($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        // test that bblier can share folder to another (14) entity in addition of 13
        $body = [
            'public' => true,
            'sharing' => [
                'entities' => [
                    [
                        'entity_id' => 13,
                        'edition' => true
                    ], [
                        'entity_id' => 14,
                        'edition' => true
                    ]
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(204, $response->getStatusCode());

        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertNotEmpty($responseBody['folder']);

        $this->assertNotEmpty($responseBody['folder']['sharing']['entities']);
        $this->assertSame(13, $responseBody['folder']['sharing']['entities'][0]['entity_id']);
        $this->assertSame(true, $responseBody['folder']['sharing']['entities'][0]['edition']);
        $this->assertSame(false, $responseBody['folder']['sharing']['entities'][0]['canDelete']);

        // test that bblier cannot share sub-folder
        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$idSub]);
        $this->assertSame(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Cannot share/unshare folder because at least one folder is out of your perimeter', $responseBody['errors']);

        // test sub-folder creation, with keeping sharing rules from parent
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
            'label'     => 'Mon troisieme dossier',
            'parent_id' => self::$idSub
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['folder']);
        self::$idSubSub = $responseBody['folder'];

        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$idSubSub]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertNotEmpty($responseBody['folder']['sharing']['entities']);
        $this->assertSame(13, $responseBody['folder']['sharing']['entities'][0]['entity_id']);
        $this->assertSame(false, $responseBody['folder']['sharing']['entities'][0]['edition']);
        $this->assertSame(false, $responseBody['folder']['sharing']['entities'][0]['canDelete']);

        // Make the folder private for next tests
        $body = [
            'public' => false,
            'sharing' => [
                'entities' => [
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->sharing($fullRequest, new \Slim\Http\Response(), ['id' => self::$idSubSub]);
        $this->assertSame(204, $response->getStatusCode());

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testUpdateMoveToFolder()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        //ERROR
        $body = [
            'label' => 'Mon troisieme dossier renomme 2',
            'parent_id'  => self::$idSubSub
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('parent_id does not exist or Id is a parent of parent_id', $responseBody['errors']);

        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
            'label'     => 'Mon dossier'
        ];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['folder']);
        self::$idMoved = $responseBody['folder'];

        $body = [
            'label' => 'Mon premier dossier deplace',
            'parent_id'  => self::$idMoved
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame(400, $response->getStatusCode(), true);

        $this->assertSame('Cannot move folder because at least one folder is out of your perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $body = [
            'label' => 'Mon premier dossier deplace',
            'parent_id'  => self::$idMoved
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame(400, $response->getStatusCode(), true);
        $this->assertSame('Parent Folder not found or out of your perimeter', $responseBody['errors']);

        // Success
        $body = [
            'label' => 'Mon troisieme dossier deplace',
            'parent_id'  => self::$id
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);
        $response     = $folderController->update($fullRequest, new \Slim\Http\Response(), ['id' => self::$idSubSub]);

        $this->assertSame(200, $response->getStatusCode(), true);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetById()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(self::$id, $responseBody->folder->id);
        $this->assertSame('Mon premier dossier renomme', $responseBody->folder->label);
        $this->assertSame(true, $responseBody->folder->public);
        $this->assertSame(null, $responseBody->folder->parent_id);
        $this->assertSame(0, $responseBody->folder->level);
        $this->assertIsArray($responseBody->folder->sharing->entities);
        $this->assertIsInt($responseBody->folder->user_id);
        $this->assertNotEmpty($responseBody->folder->user_id);
        $this->assertNotEmpty($responseBody->folder->ownerDisplayName);

        // ERROR
        $response     = $folderController->getById($request, new \Slim\Http\Response(), ['id' => '123456789']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGet()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $folderController->get($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->folders);

        foreach ($responseBody->folders as $value) {
            $this->assertNotEmpty($value->name);
            $this->assertNotEmpty($value->id);
            $this->assertIsInt($value->id);
            $this->assertNotEmpty($value->label);
            $this->assertIsBool($value->public);
            $this->assertIsInt($value->user_id);
            if (!empty($value->parent_id)) {
                $this->assertIsInt($value->parent_id);
            }
            $this->assertIsInt($value->level);
            $this->assertIsInt($value->countResources);
        }

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $response     = $folderController->get($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertEmpty($responseBody->folders);
    }

    public function testUnpinFolder()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $args = [
            'id' => self::$id
        ];

        $response     = $folderController->unpinFolder($request, new \Slim\Http\Response(), $args);

        $this->assertSame(204, $response->getStatusCode());

        // ERROR

        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->unpinFolder($fullRequest, new \Slim\Http\Response(), $args);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Folder is not pinned', $responseBody->errors);


        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->unpinFolder($fullRequest, new \Slim\Http\Response(), ['id' => self::$id + 100]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);

        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->unpinFolder($fullRequest, new \Slim\Http\Response(), ['id' => 'test']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Route id not found or is not an integer', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testPinFolder()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  UPDATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'PUT']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $args = [
            'id' => self::$id
        ];

        $response     = $folderController->pinFolder($request, new \Slim\Http\Response(), $args);

        $this->assertSame(204, $response->getStatusCode());

        // ERROR

        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->pinFolder($fullRequest, new \Slim\Http\Response(), $args);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Folder is already pinned', $responseBody->errors);


        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->pinFolder($fullRequest, new \Slim\Http\Response(), ['id' => self::$id + 100]);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);

        $fullRequest = \httpRequestCustom::addContentInBody($args, $request);
        $response     = $folderController->pinFolder($fullRequest, new \Slim\Http\Response(), ['id' => 'test']);
        $responseBody = json_decode((string)$response->getBody());

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Route id not found or is not an integer', $responseBody->errors);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetPinned()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $folderController->getPinnedFolders($request, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody());

        $this->assertNotEmpty($responseBody->folders);

        foreach ($responseBody->folders as $value) {
            $this->assertNotEmpty($value->name);
            $this->assertNotEmpty($value->id);
            $this->assertIsInt($value->id);
            $this->assertNotEmpty($value->label);
            $this->assertIsBool($value->public);
            $this->assertIsInt($value->user_id);
            if (!empty($value->parent_id)) {
                $this->assertIsInt($value->parent_id);
            }
            $this->assertIsInt($value->level);
            $this->assertIsInt($value->countResources);
        }

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testAddResourcesById()
    {
        // Create resources to add to folder

        $GLOBALS['login'] = 'cchaplin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $resController = new \Resource\controllers\ResController();

        //  CREATE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'POST']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $fileContent = file_get_contents('test/unitTests/samples/test.txt');
        $encodedFile = base64_encode($fileContent);

        $argsMailNew = [
            'modelId'          => 1,
            'status'           => 'NEW',
            'encodedFile'      => $encodedFile,
            'format'           => 'txt',
            'confidentiality'  => false,
            'documentDate'     => '2019-01-01 17:18:47',
            'arrivalDate'      => '2019-01-01 17:18:47',
            'processLimitDate' => '2029-01-01',
            'doctype'          => 102,
            'destination'      => 15,
            'initiator'        => 15,
            'subject'          => 'Breaking News : Superman is alive - PHP unit',
            'typist'           => 19,
            'priority'         => 'poiuytre1357nbvc',
            'followed'         => true,
            'diffusionList'    => [
                [
                    'id'   => 11,
                    'type' => 'user',
                    'mode' => 'dest'
                ]
            ]
        ];

        $argsMailATra = [
            'modelId'          => 1,
            'status'           => 'A_TRA',
            'encodedFile'      => $encodedFile,
            'format'           => 'txt',
            'confidentiality'  => false,
            'documentDate'     => '2019-01-01 17:18:47',
            'arrivalDate'      => '2019-01-01 17:18:47',
            'processLimitDate' => '2029-01-01',
            'doctype'          => 102,
            'destination'      => 15,
            'initiator'        => 15,
            'subject'          => 'Breaking News : Superman is alive - PHP unit',
            'typist'           => 19,
            'priority'         => 'poiuytre1357nbvc',
            'followed'         => true,
            'diffusionList'    => [
                [
                    'id'   => 11,
                    'type' => 'user',
                    'mode' => 'dest'
                ]
            ]
        ];

        $fullRequest = \httpRequestCustom::addContentInBody($argsMailNew, $request);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        self::$idFirstResource = $responseBody['resId'];
        $this->assertIsInt(self::$idFirstResource);

        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        self::$idSecondResource = $responseBody['resId'];
        $this->assertIsInt(self::$idFirstResource);

        $fullRequest = \httpRequestCustom::addContentInBody($argsMailATra, $request);
        $response     = $resController->create($fullRequest, new \Slim\Http\Response());
        $responseBody = json_decode((string)$response->getBody(), true);
        self::$idThirdResource = $responseBody['resId'];
        $this->assertIsInt(self::$idFirstResource);

        // Actual test
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // Errors
        $response     = $folderController->addResourcesById($request, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);

        $body = [];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->addResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body resources is empty or not an array', $responseBody['errors']);

        // Success
        $body = ['resources' => [self::$idFirstResource, self::$idSecondResource, self::$idThirdResource]];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->addResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['countResources']);
        $this->assertSame(3, $responseBody['countResources']);


        // Other errors
        $response     = $folderController->addResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertIsInt($responseBody['countResources']);
        $this->assertSame(3, $responseBody['countResources']);


        $response     = $folderController->addResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Folder out of perimeter', $responseBody['errors']);

        $body = ['resources' => [self::$idFirstResource * 1000, self::$idSecondResource * 1000, self::$idThirdResource * 1000]];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->addResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertSame('Resources out of perimeter', $responseBody['errors']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetResourceById()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // Errors
        $response     = $folderController->getResourcesById($request, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);

        $response     = $folderController->getResourcesById($request, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Folder out of perimeter', $responseBody['errors']);

        // Success
        $queryParams = [];
        $fullRequest = $request->withQueryParams($queryParams);

        $response     = $folderController->getResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['countResources']);
        $this->assertSame(3, $responseBody['countResources']);

        $this->assertIsArray($responseBody['allResources']);
        $this->assertSame(3, count($responseBody['allResources']));
        $this->assertSame(self::$idFirstResource, $responseBody['allResources'][0]);
        $this->assertSame(self::$idSecondResource, $responseBody['allResources'][1]);
        $this->assertSame(self::$idThirdResource, $responseBody['allResources'][2]);

        $this->assertSame(self::$idFirstResource, $responseBody['resources'][0]['resId']);
        $this->assertEmpty($responseBody['resources'][0]['chrono']);
        $this->assertEmpty($responseBody['resources'][0]['barcode']);
        $this->assertSame('Breaking News : Superman is alive - PHP unit', $responseBody['resources'][0]['subject']);
        $this->assertEmpty($responseBody['resources'][0]['confidentiality']);
        $this->assertSame('Nouveau courrier pour le service', $responseBody['resources'][0]['statusLabel']);
        $this->assertSame('fm-letter-status-new', $responseBody['resources'][0]['statusImage']);
        $this->assertSame('#009dc5', $responseBody['resources'][0]['priorityColor']);
        $this->assertEmpty($responseBody['resources'][0]['closing_date']);
        $this->assertSame(0, $responseBody['resources'][0]['countAttachments']);
        $this->assertSame(true, $responseBody['resources'][0]['hasDocument']);
        $this->assertSame(false, $responseBody['resources'][0]['mailTracking']);
        $this->assertEmpty($responseBody['resources'][0]['integrations']);
        $this->assertSame(0, $responseBody['resources'][0]['countNotes']);
        $this->assertIsArray($responseBody['resources'][0]['folders']);
        $this->assertSame(1, count($responseBody['resources'][0]['folders']));
        $this->assertIsArray($responseBody['resources'][0]['display']);
        $this->assertEmpty($responseBody['resources'][0]['display']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testGetFilters()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // Errors
        $response     = $folderController->getFilters($request, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);

        $response     = $folderController->getFilters($request, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Folder out of perimeter', $responseBody['errors']);

        $response     = $folderController->getFilters($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['entities']);
        $this->assertEmpty($responseBody['entities']);
        $this->assertIsArray($responseBody['priorities']);
        $this->assertEmpty($responseBody['priorities']);
        $this->assertIsArray($responseBody['categories']);
        $this->assertEmpty($responseBody['categories']);

        $this->assertIsArray($responseBody['statuses']);

        $this->assertSame(2, count($responseBody['statuses']));

        $this->assertSame('NEW', $responseBody['statuses'][0]['id']);
        $this->assertSame('Nouveau courrier pour le service', $responseBody['statuses'][0]['label']);
        $this->assertSame(2, $responseBody['statuses'][0]['count']);

        $this->assertSame('A_TRA', $responseBody['statuses'][1]['id']);
        $this->assertSame('PJ à traiter', $responseBody['statuses'][1]['label']);
        $this->assertSame(1, $responseBody['statuses'][1]['count']);

        $this->assertIsArray($responseBody['entitiesChildren']);
        $this->assertEmpty($responseBody['entitiesChildren']);
        $this->assertIsArray($responseBody['entitiesChildren']);
        $this->assertEmpty($responseBody['entitiesChildren']);
        $this->assertIsArray($responseBody['doctypes']);
        $this->assertEmpty($responseBody['doctypes']);
        $this->assertIsArray($responseBody['folders']);
        $this->assertEmpty($responseBody['folders']);

        //  GET
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        $response     = $folderController->getFilters($request, new \Slim\Http\Response(), ['id' => self::$idSub]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($responseBody['entities']);
        $this->assertEmpty($responseBody['entities']);
        $this->assertIsArray($responseBody['priorities']);
        $this->assertEmpty($responseBody['priorities']);
        $this->assertIsArray($responseBody['categories']);
        $this->assertEmpty($responseBody['categories']);
        $this->assertIsArray($responseBody['statuses']);
        $this->assertEmpty($responseBody['statuses']);
        $this->assertIsArray($responseBody['entitiesChildren']);
        $this->assertEmpty($responseBody['entitiesChildren']);
        $this->assertIsArray($responseBody['entitiesChildren']);
        $this->assertEmpty($responseBody['entitiesChildren']);
        $this->assertIsArray($responseBody['doctypes']);
        $this->assertEmpty($responseBody['doctypes']);
        $this->assertIsArray($responseBody['folders']);
        $this->assertEmpty($responseBody['folders']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testRemoveResourcesById()
    {
        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);

        // Errors
        $response     = $folderController->removeResourcesById($request, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Route id is not an integer', $responseBody['errors']);

        $response     = $folderController->removeResourcesById($request, new \Slim\Http\Response(), ['id' => self::$id * 1000]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Folder out of perimeter', $responseBody['errors']);

        $body = [];

        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->removeResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertNotEmpty(400, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertSame('Body resources is empty or not an array', $responseBody['errors']);

        // Success
        $body = ['resources' => [self::$idFirstResource, self::$idSecondResource]];
        $fullRequest = \httpRequestCustom::addContentInBody($body, $request);

        $response     = $folderController->removeResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());

        $responseBody = json_decode((string)$response->getBody(), true);

        $this->assertIsInt($responseBody['countResources']);
        $this->assertSame(1, $responseBody['countResources']);


        // Other errors
        $response     = $folderController->removeResourcesById($fullRequest, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode((string)$response->getBody(), true);
        $this->assertIsInt($responseBody['countResources']);
        $this->assertSame(1, $responseBody['countResources']);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }

    public function testDelete()
    {
        $GLOBALS['login'] = 'bblier';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        $folderController = new \Folder\controllers\FolderController();

        //  DELETE ERROR
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $folderController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $this->assertSame(400, $response->getStatusCode());

        $response       = $folderController->delete($request, new \Slim\Http\Response(), ['id' => 999999]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('Cannot delete because at least one folder is out of your perimeter', $responseBody->errors);

        $response       = $folderController->delete($request, new \Slim\Http\Response(), ['id' => 'wrong format']);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('Query id is empty or not an integer', $responseBody->errors);

        $GLOBALS['login'] = 'aackermann';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];

        //  DELETE
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'DELETE']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $folderController->delete($request, new \Slim\Http\Response(), ['id' => self::$id]);

        //  READ
        $environment    = \Slim\Http\Environment::mock(['REQUEST_METHOD' => 'GET']);
        $request        = \Slim\Http\Request::createFromEnvironment($environment);
        $response       = $folderController->getById($request, new \Slim\Http\Response(), ['id' => self::$id]);
        $responseBody   = json_decode((string)$response->getBody());

        $this->assertSame('Folder not found or out of your perimeter', $responseBody->errors);

        // DELETE TEST RESOURCES
        \Resource\models\ResModel::delete([
            'where' => ['res_id in (?)'],
            'data' => [[self::$idFirstResource, self::$idSecondResource, self::$idThirdResource]]
        ]);

        $res = \Resource\models\ResModel::getById(['resId' => self::$idFirstResource, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertEmpty($res);

        $res = \Resource\models\ResModel::getById(['resId' => self::$idSecondResource, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertEmpty($res);

        $res = \Resource\models\ResModel::getById(['resId' => self::$idThirdResource, 'select' => ['*']]);
        $this->assertIsArray($res);
        $this->assertEmpty($res);

        $GLOBALS['login'] = 'superadmin';
        $userInfo = \User\models\UserModel::getByLogin(['login' => $GLOBALS['login'], 'select' => ['id']]);
        $GLOBALS['id'] = $userInfo['id'];
    }
}
