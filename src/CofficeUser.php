<?php
namespace yzyblog\coffice_service;

use dekuan\delib\CLib;

Class CofficeUser
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
     * @return CofficeUser
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

    /**
     * 用户注册
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function users( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['username', 'password'] )
          && CLib::IsExistingString( $this->arrInput['username'] )
          && CLib::IsExistingString( $this->arrInput['password'] )
          && CofficeAuth::GetInstance()->initialize()
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


    /**
     * 用户信息
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function Info( $userObjectId, & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsExistingString( $userObjectId )
            && CLib::IsArrayWithKeys ( $this->arrInput, ['userToken'] )
            && CLib::IsExistingString( $this->arrInput['userToken'] )
            && CofficeAuth::GetInstance()->initialize()
        )
        {
            $arrExist = app('db')->table('_User')->where('userToken', $this->arrInput['userToken'])->first();

            if( CLib::IsArrayWithKeys( $arrExist ) && $userObjectId == $arrExist['_id'] && $arrExist['tokenInvalidAt'] > time() )
            {
                $nRet = CofficeConst::ERROR_SUCCESS;

                $arrSetupTable = app('db')->table( CofficeConst::$m_str_SetupTablesColumn )->select('column')->where([
                    'className' => '_User',
                    'display'   => 0
                ])->get();

                $arrOutPutData['user_id'] = $arrExist['_id'];
                foreach ( $arrSetupTable as $arrVal)
                {
                    $arrOutPutData[ $arrVal['column'] ] = $arrExist[ $arrVal['column'] ];
                }

                $this->refreshTokenInvalidAt( $userObjectId );
            }
        }

        return $nRet;
    }



    /**
     * 登陆
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function login( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['username', 'password'] )
            && CLib::IsExistingString( $this->arrInput['username'] )
            && CLib::IsExistingString( $this->arrInput['password'] )
            && CofficeAuth::GetInstance()->initialize()
        )
        {
            $arrExist = app('db')->table('_User')->where('username', $this->arrInput['username'])->first();

            if( CLib::IsArrayWithKeys( $arrExist ) )
            {
                $password = $this->getEncrypt( $this->arrInput['password'], $arrExist['salt'] );

                if( $arrExist['password'] == $password )
                {
                    $nRet = CofficeConst::ERROR_SUCCESS;
                    $arrData = array();
                    $arrOutPutData['user_id']   = $arrExist['_id'];
                    $arrSetupTable = app('db')->table( CofficeConst::$m_str_SetupTablesColumn )->select('column')->where([
                        'className' => '_User',
                        'display'   => 0
                    ])->get();

                    foreach ( $arrSetupTable as $arrVal)
                    {
                        $arrOutPutData[ $arrVal['column'] ] = $arrExist[ $arrVal['column'] ];
                    }

                    if( $arrExist['tokenInvalidAt'] > time() )
                    {
                        $arrOutPutData['userToken'] = $arrExist['userToken'];
                    }
                    else
                    {
                        $arrData = [
                            'userToken' => $this->getUserToken( $arrExist['salt'] ),
                        ];
                        $arrOutPutData['userToken'] = $arrData['userToken'];
                    }

                    $this->refreshTokenInvalidAt( $arrExist['_id'], $arrData );
                }
                else
                {
                    $sErroeMsg = '用户名或密码错误';
                }
            }
        }

        return $nRet;
    }


    /**
     * 重置密码
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function repassword( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if(  CLib::IsArrayWithKeys ( $this->arrInput, ['password'] )
          && CLib::IsExistingString( $this->arrInput['password'] )
          && CofficeAuth::GetInstance()->initialize()
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


    /**
     * 生成 userToken
     * @param $salt
     * @return string
     */
    private function getUserToken( $salt )
    {
        return md5( $salt . time() . mt_rand() );
    }

    /**
     * 刷新userToken 失效时间
     * @param $userObjectId
     * @param $arrData
     */
    private function refreshTokenInvalidAt( $userObjectId, $arrData = array() )
    {
        $arrData['tokenInvalidAt'] = time() + 86400;
        app('db')->table('_User')->where( '_id', $userObjectId )->update( $arrData );
    }


    /**
     * 密码加密
     * @param $password
     * @param $salt
     * @return string
     */
    private function getEncrypt( $password, $salt )
    {
        return md5( md5( $password.'-'.$salt ).$salt );
    }

    private function _Init()
    {
        // 数据过滤
        $this->arrInput = app('request')->input();

        $this->userObjectId   = CofficeAuth::GetInstance()->getUserObjectID();
    }

}