<?php
namespace yzyblog\coffice_service;

use dekuan\delib\CLib;

Class CofficeAuth{

    /**
     * Input
     *
     * @var arr
     */
    private $arrInput;

    /**
     * Application ID
     *
     * @var string
     */
    private $appId;

    /**
     * Application Key
     *
     * @var string
     */
    private $appKey;

    /**
     * Application master key
     *
     * @var string
     */
    private $appMasterKey;

    /**
     * Use master key or not
     *
     * @var bool
     */
    private $useMasterKey = false;

    /**
     * Use Class name
     *
     * @var string
     */
    private $useClassName;

    /**
     * Use Class ObjectID
     *
     * @var string
     */
    private $useClassID;
    /**
     * Use Class ObjectID
     *
     * @var string
     */
    private $requestType;

    /**
     * Use Class userRole
     *
     * @var string
     */
    private $userRole;

    /**
     * Use Class userObjectID
     *
     * @var string
     */
    private $userObjectId;

    protected static $g_cStaticInstance;

    /**
     * Initialize application key and settings
     *
     * @param string $appId        Application ID
     * @param string $appKey       Application key
     * @param string $appMasterKey Application master key
     */


    /**
     * @return CofficeAuth
     */
    static function GetInstance()
    {
        if (is_null(self::$g_cStaticInstance) || !isset(self::$g_cStaticInstance))
        {
            self::$g_cStaticInstance = new self();
        }
        return self::$g_cStaticInstance;
    }

    public function __construct()
    {
        $this->arrInput = app('request')->input();
        $this->initialize();
    }

    /**
     * 验证请求合法
     * @return bool
     */
    public function initialize()
    {
        $bRtn = false;

        if(  CLib::IsArrayWithKeys( $this->arrInput, ['app_id', 'app_sign'] ) &&
            CLib::IsExistingString( $this->arrInput['app_id'] ) &&
            CLib::IsExistingString( $this->arrInput['app_sign'] ) )
        {

            $this->appId        = $this->arrInput['app_id'];

            $arrExist = app('db')->connection('mongodb_coffice')->table('app_list')->find( $this->appId );

            if( CLib::IsArrayWithKeys( $arrExist ) )
            {
                $this->appKey = $arrExist['app_key'];

                $list = explode( ',', $this->arrInput['app_sign'] );

                if( CLib::IsArrayWithKeys( $list, 2 ) && 'master' == $list[2] )
                {
                    $this->appMasterKey = $arrExist['master_key'];
                }

                $arrAuth = $this->verifySign( $list[1] );

                // 判断时区 Todo
                // $list[1] = time();

                if( $arrAuth == $this->arrInput['app_sign'] && ( $list[1] + 5 ) > time() )
                {
                    $this->setInfo( $arrExist['dbs'] );
                    $this->setClassInfo();
                    $this->setUserInfo();

                    if( CLib::IsExistingString( $this->appMasterKey ) )
                    {
                        $this->useMasterKey = true;
                    }

                    $bRtn = $this->allowAuth();
                }
            }
        }

        return $bRtn;
    }


    /**
     * 设置数据库
     * @param $dbs
     */
    public function setInfo( $dbs )
    {
        app('config')->set('database.connections.mongodb_account.database', $dbs );
    }


    /**
     * app_sign 加密校验
     * @param $timestamp
     * @return string
     */
    private function verifySign( $timestamp )
    {
        $key       = $this->appMasterKey ?: $this->appKey;
        $sign      = md5($timestamp . $key);
        $sign     .= "," . $timestamp;

        if ( $this->appMasterKey )
        {
            $sign .= ",master";
        }

        return $sign;
    }

    /**
     * 设置ClassName ClassID
     */
    private function setClassInfo()
    {
        $this->requestType = app('request')->method();

        $classInfo = explode( '/', app('request')->path() );

        if( 'user' == $classInfo[1] )
        {
            $classInfo[1] = '_User';
        }

        $this->useClassName = $classInfo[1];

        if( count( $classInfo ) == 3 )
        {
            $this->useClassID = $classInfo[2];
        }

    }

    /**
     * 设置 userObjectId UserRole
     */
    private function setUserInfo()
    {
        if( CLib::IsArrayWithKeys( $this->arrInput, ['userToken'] ) &&
            CLib::IsExistingString( $this->arrInput['userToken'] ) )
        {
            // 加入有效期判断
            $arrExist = app('db')->table('_User')->where( 'userToken', $this->arrInput['userToken'] )->first();

            if( CLib::IsArrayWithKeys( $arrExist ) )
            {
                $this->userObjectId = strval( $arrExist['_id'] );

                $arrRole = array();

                $arrExist = app('db')->table('_Role')->where( 'userObjectId', $this->userObjectId )->get()->toArray();

                foreach ( $arrExist as $v )
                {
                    $arrRole[] = $v['roleObjectId'];
                }

                $this->userRole = $arrRole;

            }
        }

    }


    /**
     * ACL表权限验证
     * @param $action
     * @return bool
     */
    private function allowAuth()
    {
        $bRtn = false;

        if( $this->useMasterKey )
        {
            $bRtn = true;
        }
        else
        {
            if( ! CLib::IsArrayWithKeys( CofficeConst::$arrNoAllowClass, $this->useClassName ) )
            {
                $arrOperat = [
                    'GET'     => 'read',
                    'PUT'     => 'write',
                    'POST'    => 'write',
                    'DELETE'  => 'delete'
                ];

                $arrExist = app('db')->table('_SetupTables')->where( 'className', $this->useClassName )->where(function($query) use ($arrOperat)
                {
                    $query->where( 'ACL.*.'.$arrOperat[$this->requestType], true );

                    if( CLib::IsArrayWithKeys( $this->userRole ) )
                    {
                        foreach( $this->userRole as $v )
                        {
                            $query->orWhere( 'ACL.role:'.$v.'.'.$arrOperat[$this->requestType] , true );
                        }
                    }

                    if( CLib::IsExistingString( $this->userObjectId ) )
                    {
                        $query->orWhere( 'ACL.user:'.$this->userObjectId.'.'.$arrOperat[$this->requestType], true );
                    }
                });

                $arrExist = $arrExist->first();

                if( CLib::IsArrayWithKeys( $arrExist ) )
                {
                    $bRtn = true;
                }
            }
        }

        return $bRtn;
    }

    /**
     * @return string
     */
    public function getUseClassName()
    {
        return $this->useClassName;
    }

    /**
     * @return string
     */
    public function getUseClassID()
    {
        return $this->useClassID;
    }

    /**
     * @return boolean
     */
    public function isUseMasterKey()
    {
        return $this->useMasterKey;
    }

    /**
     * @return string
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * @return string
     */
    public function getUserObjectID()
    {
        return $this->userObjectId;
    }



}
