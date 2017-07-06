<?php
namespace yzyblog\coffice_service;

use dekuan\delib\CLib;
use yzyblog\coffice_service\CofficeConst;

Class CUser
{


    /**
     * User ObjectID
     *
     * @var string
     */
    private $userObjectId;

    private $arrInput;

    protected static $g_cStaticInstance;

    /**
     * @return CUser
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
        $this->_Init();
        $this->arrInput = app('request')->input();
    }

    public function users( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['username', 'password'] )
          && CLib::IsExistingString( $this->arrInput['username'] )
          && CLib::IsExistingString( $this->arrInput['password'] )
          && CAuth::GetInstance()->initialize()
        )
        {
            $nExist = app('db')->table('_User')->where('username', $this->arrInput['username'])->count();

            if( $nExist == 0 )
            {
                $setupACL = app('db')->table( CofficeConst::$m_str_SetupTablesName )->where('className', '_User')->first();
                $salt = Coffice::getRandomID();
                $password = $this->getEncrypt( $this->arrInput['password'], $salt );

                $arrUserInfo = [
                    '_id'      => Coffice::getRandomID(),
                    'username' => $this->arrInput['username'],
                    'password' => $password,
                    'salt'     => $salt,
                    'userToken' => $this->getUserToken( $salt ),
                    'tokenInvalidAt' => time() + 86400,
                    'ACL'       => $setupACL['columnACL']
                ];

                if( app('db')->table('_User')->insert($arrUserInfo) )
                {
                    $nRet = CofficeConst::ERROR_SUCCESS;
                    $arrOutPutData['userToken'] = $arrUserInfo['userToken'];
                    $arrOutPutData['user_id'] = $arrUserInfo['_id'];
                }
            }
            else
            {
                $sErroeMsg = '用户名已存在';
            }

        }

        return $nRet;
    }


    public function login( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['username', 'password'] )
            && CLib::IsExistingString( $this->arrInput['username'] )
            && CLib::IsExistingString( $this->arrInput['password'] )
            && CAuth::GetInstance()->initialize()
        )
        {
            $arrExist = app('db')->table('_User')->where('username', $this->arrInput['username'])->first();

            if( CLib::IsArrayWithKeys( $arrExist ) )
            {
                $password = $this->getEncrypt( $this->arrInput['password'], $arrExist['salt'] );

                if( $arrExist['password'] == $password )
                {
                    $nRet = CofficeConst::ERROR_SUCCESS;
                    $setupACL = array();
                    $arrOutPutData['user_id']   = $arrExist['_id'];
                    $arrSetupTable = app('db')->table( CofficeConst::$m_str_SetupTablesColumn )->select('column')->where([
                        'className' => '_User',
                        'display'   => 0
                    ])->get();

                    if( $arrExist['tokenInvalidAt'] > time() )
                    {
                        $arrOutPutData['userToken'] = $arrExist['userToken'];
                        $arrData = [
                            'tokenInvalidAt' => time() + 86400
                        ];
                    }
                    else
                    {
                        $arrData = [
                            'userToken' => $this->getUserToken( $arrExist['salt'] ),
                            'tokenInvalidAt' => time() + 86400
                        ];
                        $arrOutPutData['userToken'] = $arrData['userToken'];
                    }
                    app('db')->table('_User')->where( '_id', $arrExist['_id'] )->update( $arrData );

                    foreach ( $arrSetupTable as $arrVal)
                    {
                        $arrOutPutData[ $arrVal['column'] ] = $arrExist[ $arrVal['column'] ];
                    }
                }
                else
                {
                    $sErroeMsg = '用户名或密码错误';
                }
            }
        }

        return $nRet;
    }


    public function repassword( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['password'] )
          && CLib::IsExistingString( $this->arrInput['password'] )
          && CAuth::GetInstance()->initialize()
        )
        {
            $where = [];
            $arrExist = app('db')->table('_User')->where('_id', $this->userObjectId)->first();

            if( CLib::IsArrayWithKeys( $arrExist ) )
            {
                if( CLib::IsArrayWithKeys ( $this->arrInput, 'old_password' ) )
                {
                    $old_password = $this->getEncrypt( $this->arrInput['old_password'], $arrExist['salt'] );
                    $where['password'] = $old_password;
                }

                $salt = Coffice::getRandomID();
                $arrData = [
                    'salt'           => $salt,
                    'password'       => $this->getEncrypt( $this->arrInput['password'], $salt ),
                    'userToken'      => $this->getUserToken( $salt ),
                    'tokenInvalidAt' => time() + 86400
                ];

                if( app('db')->table('_User')->where( $where )->update( $arrData ) )
                {
                    $nRet = CofficeConst::ERROR_SUCCESS;
                    $arrOutPutData['userToken'] = $arrData['userToken'];
                }
                else
                {
                    $sErroeMsg = '修改失败';
                }
            }
            else
            {
                $sErroeMsg = '请先登录';
            }
        }

        return $nRet;
    }


    private function getUserToken( $salt )
    {
        return md5( $salt . time() . mt_rand() );
    }


    private function getEncrypt( $password, $salt )
    {
        return md5( md5( $password.'-'.$salt ).$salt );
    }

    function getUserInfo( $where )
    {

    }


    // 校验登录
    public static function checkLogin()
    {
        $bRtn = false;
        if( 0 )
        {
            // ...校验token和userobjid正确
//            md5( str_shuffle( uniqid( microtime() . mt_rand() ) ) . $sType ); userToken
        }

        return $bRtn;
    }


    public static function getUserObjectId()
    {
        return self::$UserObjectId;
    }



    private function _Init()
    {
        // 数据过滤
        $this->arrInput = app('request')->input();

        $this->userObjectId   = CAuth::GetInstance()->getUserObjectID();
    }

}
