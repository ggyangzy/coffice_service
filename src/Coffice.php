<?php

namespace yzyblog\coffice_service;

use dekuan\delib\CLib;

Class Coffice
{

    /**
     * 单例
     * @var object
     */
    protected static $g_cStaticInstance;

    /**
     * 结果输出
     * @var array
     */
    protected $m_arrOutputData;

    /**
     * 提交参数
     * @var 输入
     */
    protected $m_arrInputData;

    /**
     * 前台允许查询字段
     * @var array
     */
    protected $m_arrSelectAllow;

    /**
     * 表对象
     * @var object
     */
    protected $m_oDBLink;

    /**
     * 操作表名
     * @var string
     */
    protected $m_sDBTableName;

    /**
     * master用户标识
     * @var boolean
     */
    protected $m_bUseMaster;

    /**
     * 操作表主键ID
     * @var string
     */
    protected $m_sUseClassID;

    /**
     * 登录用户ID
     * @var string
     */
    protected $m_sUserObjectID;

    /**
     * 用户所属角色
     * @var array
     */
    protected $m_arrUserRole;

    /**
     * 默认分页条数
     * @var int
     */
    protected $m_itake;


    /**
     * @return Coffice
     */
    static function GetInstance()
    {
        if (is_null(self::$g_cStaticInstance) || !isset(self::$g_cStaticInstance))
        {
            self::$g_cStaticInstance = new self();
        }
        return self::$g_cStaticInstance;
    }

    /**
     * Coffice constructor.
     */
    public function __construct()
    {
        $this->_Init();
    }


    /**
     * find查询操作
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function find( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if( CofficeAuth::GetInstance()->initialize() )
        {
            $result = array();
            $nRet = CofficeConst::ERROR_SUCCESS;
            $arrResultColumn = $this->_getTablesColumn(true);

            if( CLib::IsArrayWithKeys( $arrResultColumn ) )
            {
                if( $this->m_bUseMaster )
                {
                    $arrResultColumn[] = 'ACL';
                }

                $arrGet   = $this->_getArrDataTosKey('get');

                $this->_getDBACL( 'read' );

                // 拼接查询条件
                $this->_getDBWhere();

                // 获取sum num max min avg值
                $result = $this->_getDBGetData();

                // 拼接排序 分页等操作
                $this->_getDBOtherData( $result );

                $arrDisplayColumn = array_merge( $arrResultColumn, ['_id','createAt','updateAt'] );

                if ( CLib::IsArrayWithKeys( $arrGet, 'item' ) )
                {
                    $result['result'] = $this->m_oDBLink->first( $arrDisplayColumn );
                }
                else
                {
                    $result['result'] = $this->m_oDBLink->get( $arrDisplayColumn );
                }
            }

            $arrOutPutData = $result;
        }

        return $nRet;
    }

    /**
     * 查询单条记录
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function show( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if( CofficeAuth::GetInstance()->initialize() )
        {
            $result = array();

            $nRet = CofficeConst::ERROR_SUCCESS;

            $this->_getDBACL( 'read' );

            $this->_getDBWhere( $this->m_sUseClassID );

            $this->_getDBOtherData();

            $arrResultColumn = $this->_getTablesColumn(true);

            if( CLib::IsArrayWithKeys( $arrResultColumn ) )
            {
                if( $this->m_bUseMaster )
                {
                    $arrResultColumn[] = 'ACL';
                }

                $arrDisplayColumn = array_merge( $arrResultColumn, ['_id','createAt','updateAt'] );

                $arrData = $this->m_oDBLink->first( $arrDisplayColumn );

                if( CLib::IsArrayWithKeys( $arrData ) )
                {
                    $result['result'] = $arrData;
                }
            }

            $arrOutPutData = $result;
        }

        return $nRet;
    }

    /**
     * 添加记录
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function post( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if( CofficeAuth::GetInstance()->initialize() )
        {
            if( $this->_SaveData( $arrOutPutData, $sErroeMsg ) )
            {
                $nRet = CofficeConst::ERROR_SUCCESS;
            }
            else
            {
                $nRet = CofficeConst::ERROR_ACCESS_EXEC_ERROR;
            }

        }

        return $nRet;
    }

    /**
     * 修改记录
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function put( & $arrOutPutData, & $sErroeMsg  )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if( CofficeAuth::GetInstance()->initialize() )
        {
            $this->_getDBACL( 'write' );

            if( $this->_SaveData( $arrOutPutData, $sErroeMsg, $this->m_sUseClassID ) )
            {
                $nRet = CofficeConst::ERROR_SUCCESS;
            }
            else
            {
                $nRet = CofficeConst::ERROR_ACCESS_EXEC_ERROR;
            }
        }

        return $nRet;
    }

    /**
     * 删除记录
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public function delete( & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

        if( CofficeAuth::GetInstance()->initialize() )
        {
            $arrId = explode( '.', $this->m_sUseClassID );

            $this->_getDBACL( 'delete' );

            $this->_getDBWhere();

            if( ! empty( $arrId[1] ) )
            {
                $bStatus = $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[0], $arrId[1] ) )
                    ->delete();
            }
            else
            {
                $bStatus = $this->m_oDBLink->where( '_id' , $arrId[0] )
                    ->delete();
            }
            if( $bStatus )
            {
                $nRet = CofficeConst::ERROR_SUCCESS;
            }
            else
            {
                $sErroeMsg = '操作失败';
            }
        }

        return $nRet;
    }


    /**
     * 获取主键ID
     * @return string
     */
    public static function getRandomID()
    {
        return substr( md5( str_shuffle( uniqid( microtime() . mt_rand() ) ) ),8,16 );
    }




    ////////////////////////////////////////////////////////////////////////////////
    //  Private
    //


    /**
     * 保存 / 修改对象
     * @param string $id
     * @return int
     */
    private function _SaveData( & $arrOutputData, & $sErroeMsg, $id = '' )
    {
        $nRet = false;

        $arrTablesData = array();

        $arrTablesData = $this->_CheckTablesData( $arrOutputData, $sErroeMsg, $id );

        if( CLib::IsArrayWithKeys( $arrTablesData ) )
        {
            $this->_getDBWhere();

            $this->_getDBOtherData();

            if( '' != $id )
            {
                $arrId = explode('.', $id);

                $arrTablesData['updateAt'] = time();

                if( ! empty( $arrId[1] ) )
                {
                    $nRet = $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[0], $arrId[1] ) )
                        ->update( $arrTablesData );
                }
                else
                {
                    $nRet = $this->m_oDBLink->where( '_id' , $arrId[0] )
                        ->update( $arrTablesData );
                }
            }
            else
            {
                $arrTablesData['createAt'] = time();
                $arrTablesData['_id']    = self::getRandomID();
                $nRet = $this->m_oDBLink->insert( $arrTablesData );

                if( $nRet )
                {
                    $arrOutputData['_id'] = $arrTablesData['_id'];
                }

            }

            if( ! $nRet )
            {
                $sErroeMsg = '操作失败';
            }
        }

        return $nRet;
    }



    /**
     * 处理用户提交数据
     * @param $sFlag
     */
    private function _CheckTablesData( &$arrOutputData, &$sErroeMsg, $id  )
    {
        $arrPostData = array();
        $arrDataRule = array();
        $arrColumn   = array();

        if( CofficeConst::$m_str_SetupTablesColumn == $this->m_sDBTableName )
        {
            if( $this->m_bUseMaster )
            {
                // 获取数据
                $arrColumn      =  CofficeConst::$m_arr_SetupTablesColumnList;
                // 验证规则
                $arrDataRule    = CofficeConst::$m_arr_SetupTablesColumnListRule;
                // 字段描述
                 $arrDataDesc   = CofficeConst::$m_arr_SetupTablesColumnListDesc;
                // 字段类型
                $arrDataType    = CofficeConst::$m_arr_SetupTablesColumnListType;
                // 默认值
                $arrDataDefault = array();
            }
            else
            {
                $sErroeMsg   = '错误请求';
            }
        }
        elseif( CofficeConst::$m_str_SetupTablesName == $this->m_sDBTableName )
        {
            if( $this->m_bUseMaster )
            {
                // 获取数据
                $arrColumn      =  CofficeConst::$m_arr_SetupTablesList;
                // 验证规则
                $arrDataRule    = CofficeConst::$m_arr_SetupTablesRule;
                // 字段描述
                 $arrDataDesc   = CofficeConst::$m_arr_SetupTablesDesc;
                // 字段类型
                $arrDataType    = CofficeConst::$m_arr_SetupTablesType;
                // 默认值
                $arrDataDefault = array();
            }
            else
            {
                $sErroeMsg   = '错误请求';
            }
        }
        else
        {
            $arrSetOtherData = $this->_getTablesColumn();

            // 提交数据时过滤掉前台不显示数据
            foreach ( $arrSetOtherData as $sKey => $sVal)
            {
                $arrColumn[]                          = $sVal[ 'column' ];
                $arrDataDefault[ $sVal[ 'column' ] ] = $sVal[ 'default' ];
                $arrDataRule   [ $sVal[ 'column' ] ] = $sVal[ 'verify' ];
                $arrDataDesc   [ $sVal[ 'column' ] ] = $sVal[ 'describe' ];
                $arrDataType   [ $sVal[ 'column' ] ] = $sVal[ 'type' ];
            }
        }

        if( CLib::IsArrayWithKeys( $arrColumn ) )
        {
            foreach( $arrColumn as $sCv )
            {
                if( '' != $id )
                {
                    if( isset( $this->m_arrInputData[$sCv] ) )
                    {
                        $arrPostData[$sCv] = $this->m_arrInputData[$sCv];
                    }
                    else
                    {
                        unset( $arrDataRule[$sCv] );
                    }
                }
                else
                {
                    $arrPostData[$sCv] = isset( $this->m_arrInputData[$sCv] ) ? $this->m_arrInputData[$sCv] : '';
                }
            }

            // 校验字段验证信息
            foreach ($arrDataRule as $sSKey => $arrVal)
            {
                foreach ($arrVal as $sKey => $sVal)
                {
                    // 唯一验证
                    if( 'unique' == $sVal )
                    {
                        if( CofficeConst::$m_str_SetupTablesColumn != $this->m_sDBTableName )
                        {
                            $arrDataRule[$sSKey][$sKey] = "unique:$this->m_sDBTableName,$sSKey";

                            if( '' != $id )
                            {
                                $arrId = explode('.', $id);

                                if( ! empty( $arrId[1] ) )
                                {
                                    $arrDataRule[$sSKey][$sKey] .= ",$arrId[1],$arrId[0]";
                                }
                                else
                                {
                                    $arrDataRule[$sSKey][$sKey] .= ",$arrId[0],_id";
                                }
                            }
                        }
                        else
                        {
                            if( !empty( $arrPostData['className'] ) && !empty( $arrPostData['column'] ) )
                            {
                                $objQuery = app('db')->table( CofficeConst::$m_str_SetupTablesColumn)
                                    ->where('className',  $arrPostData['className'])
                                    ->where('column', $arrPostData['column']);

                                if( '' != $id )
                                {
                                    $arrId = explode('.', $id);

                                    if( ! empty( $arrId[1] ) )
                                    {
                                        $objQuery->where($arrId[0], '!=', $arrId[1]);
                                    }
                                    else
                                    {
                                        $objQuery->where('_id', '!=', $arrId[0]);
                                    }
                                }

                                if($objQuery->count() > 0)
                                {
                                    $sErroeMsg = '列名已存在';
                                    break 2;
                                }
                                unset($arrDataRule[$sSKey]);
                            }
                            else
                            {
                                $sErroeMsg = '错误请求';
                            }
                        }
                    }
                }
            }

            if( '' == $sErroeMsg)
            {
                foreach ($arrPostData as $sKey => $vVal)
                {
                    if ('' != @$arrDataDefault[$sKey] && '' == $vVal)
                    {
                        $arrPostData[$sKey] = $arrDataDefault[$sKey];
                    }
                }

                $validator   = app( 'validator' )->make( $arrPostData, $arrDataRule, [], $arrDataDesc );

                if( $validator->passes() )
                {
                    // 转换字段类型
                    foreach ($arrPostData as $sKey => $vVal)
                    {
                        // 类型验证
                        if( in_array( $arrDataType[ $sKey ] ,CofficeConst::$m_arr_StrData ) )
                        {
                            $arrPostData[ $sKey ] = strval( $arrPostData[ $sKey ] );
                        }
                        elseif( in_array( $arrDataType[ $sKey ] ,CofficeConst::$m_arr_IntData ) )
                        {
                            $arrPostData[ $sKey ] = intval( $arrPostData[ $sKey ] );
                        }
                        elseif( in_array( $arrDataType[ $sKey ] ,CofficeConst::$m_arr_ArrData ) )
                        {
                            if( ! is_array($arrPostData[ $sKey ]) )
                            {
                                $arrPostData[ $sKey ] = [ $arrPostData[ $sKey ] ];
                            }
                            else
                            {
                                $arrPostData[ $sKey ] = $arrPostData[ $sKey ];
                            }
                        }
                        else
                        {
                            $arrPostData[ $sKey ] = strval( $arrPostData[ $sKey ] );
                        }
                    }
                }
                else
                {
                    $sErroeMsg = $validator->messages()->first();
                }
            }

            if( '' == $id
                && $this->m_sDBTableName != CofficeConst::$m_str_SetupTablesName
                && $this->m_sDBTableName != CofficeConst::$m_str_SetupTablesColumn
            )
            {
                $arrExist = app('db')->table('_SetupTables')->where( 'className', $this->m_sDBTableName )->first();

                if( empty( $this->m_arrInputData['ACL'] ) )
                {
                    $arrPostData['ACL'] = $arrExist['columnACL'];
                }
            }

            if( '' != $sErroeMsg)
            {
                $arrPostData = array();
            }
        }

        return $arrPostData;
    }


    /**
     * ACL权限控制
     * @param $action
     */
    private function _getDBACL( $action )
    {
        if( $this->m_bUseMaster )
        {
            return;
        }

        $this->m_oDBLink->where(function($query) use ($action)
        {
            $query->where( 'ACL.*.'.$action, true );

            if( CLib::IsArrayWithKeys( $this->m_arrUserRole ) )
            {
                foreach( $this->m_arrUserRole as $v )
                {
                    $query->orWhere( 'ACL.role:'.$v.'.'.$action , true );
                }
            }

            if( CLib::IsExistingString( $this->m_sUserObjectID ) )
            {
                $query->orWhere( 'ACL.user:'.$this->m_sUserObjectID.'.'.$action, true );
            }
        });

    }

    /**
     * 获取对应表的设置数据
     * @param boolean $vFlag
     * @return array
     *  $vFlag为false 返回所有数据
     *           true 只返回相关的字段
     */
    private function _getTablesColumn( $vFlag = false )
    {
        $arrTablesColumn = array();

        $objTablesData = app('db')->table( CofficeConst::$m_str_SetupTablesColumn );

        $objTablesData->orderBy('sort','desc');

        if( $this->m_sDBTableName == CofficeConst::$m_str_SetupTablesColumn )
        {
            if( $this->m_bUseMaster && true == $vFlag )
            {
                $arrTablesColumn = CofficeConst::$m_arr_SetupTablesColumnList;
            }
        }
        elseif( $this->m_sDBTableName == CofficeConst::$m_str_SetupTablesName )
        {
            if( $this->m_bUseMaster && true == $vFlag )
            {
                $arrTablesColumn = CofficeConst::$m_arr_SetupTablesList;
            }
        }
        else
        {
            $objTablesData->where('className', $this->m_sDBTableName);

            if( ! $this->m_bUseMaster )
            {
                $objTablesData->where('display', 0);
            }

            if( true == $vFlag )
            {
                $arrResult = $objTablesData->get( ['column'] );

                foreach ($arrResult as $sVal)
                {
                    $arrTablesColumn[] = $sVal['column'];
                }
            }
        }

        if( false == $vFlag )
        {
            $arrTablesColumn = $objTablesData->get();
        }

        return $arrTablesColumn;
    }


    /**
     * 拼接查询条件(where)
     * @param string $id
     * @return mixed
     */
    private function _getDBWhere( $id = '' )
    {
        $arrWhere = $this->_getArrDataTosKey('where');

        $conform  = [
            'gt' => '>' ,
            'ge' => '>=',
            'lt' => '<' ,
            'le' => '<=',
            'eq' => '=' ,
            'ne' => '!=',
            'lk' => 'like'
        ];

        if( CLib::IsArrayWithKeys( $arrWhere ) )
        {
            foreach ( $arrWhere as $key => $val )
            {

                if( ! CLib::IsArrayWithKeys( $val ) )
                {
                    $this->m_oDBLink->where( $key , $this->_getVarType( $key, $val ) );
                }
                elseif( '_or' == $key )
                {
                    foreach ( $val as $sKey => $sVal  )
                    {
                        if( CLib::IsArrayWithKeys( $sVal ) && array_key_exists( $sVal[0] , $conform ) )
                        {
                            $this->m_oDBLink->orWhere( $sKey , $conform[ $sVal[0] ], $this->_getVarType( $sKey, $sVal[1] ) );
                        }
                        elseif ( ! CLib::IsArrayWithKeys( $sVal ) )
                        {
                            $this->m_oDBLink->orWhere( $sKey , $this->_getVarType( $sKey, $sVal ) );
                        }
                    }
                }
                elseif( CLib::IsArrayWithKeys( $val ) )
                {

                    if( CLib::IsExistingString( $val[0] ) && array_key_exists( $val[0] , $conform ) )
                    {
                        $this->m_oDBLink->where( $key , $conform[ $val[0] ], $this->_getVarType( $key, $val[1] ) );
                    }
                    elseif( 'in' == $val[0] )
                    {
                        $this->m_oDBLink->whereIn( $key , $val[1] );
                    }
                    elseif( 'nin' == $val[0] )
                    {
                        $this->m_oDBLink->whereNotIn( $key , $val[1] );
                    }
                    elseif( 'bw' == $val[0] )
                    {
                        $this->m_oDBLink->whereBetween( $key , $val[1] );
                    }
                    elseif( 'nbw' == $val[0] )
                    {
                        $this->m_oDBLink->whereNotBetween( $key , $val[1] );
                    }
                }
            }
        }

        if( CLib::IsExistingString( $id ) )
        {
            $arrId = explode('.', $id);

            if( ! empty( $arrId[1] ) )
            {
                $this->m_oDBLink->where( $arrId[0] , $this->_getVarType( $arrId[0], $arrId[1] ) );
            }
            else
            {
                $this->m_oDBLink->where( '_id' , $arrId[0] );
            }
        }

        return $this->m_oDBLink;

    }


    /**
     * 拼查询条件返回数据(other)
     * @param string $id
     * @return array
     */
    private function _getDBOtherData( & $result = [] )
    {
        $arrOther = $this->_getArrDataTosKey('other');

        if( array_key_exists( 'order' , $arrOther ) )
        {
            if( CLib::IsArrayWithKeys( $arrOther['order'] ) )
            {
                if( 'asc' == $arrOther['order'][1] )
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'asc' );
                }
                else
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'desc' );
                }
            }
            else
            {
                $this->m_oDBLink->orderBy( $arrOther['order'] , 'desc' );
            }
        }

        if( array_key_exists( 'group' , $arrOther ) )
        {
            if( CLib::IsExistingString( $arrOther['group'] ) )
            {
                $this->m_oDBLink->groupBy( $arrOther['group'] );
            }
        }

        if( array_key_exists( 'num' , $arrOther ) )
        {
            if( CLib::IsExistingString( @$arrOther['num'] ) )
            {
                $arrResultColumn = $this->_getTablesColumn(true);

                if( CLib::IsArrayWithKeys( $arrResultColumn ) )
                {
                    $arrDisplayColumn = array_merge( $arrResultColumn, ['_id','createAt','updateAt'] );

                    $result['num'] = count( $this->m_oDBLink->get( $arrDisplayColumn ) );
                }
            }
        }

        if( ! array_key_exists( 'limit' , $arrOther ) )
        {
            $this->m_oDBLink->take( $this->m_itake );
        }

        if( CLib::IsArrayWithKeys( $arrOther ) )
        {
            if( array_key_exists( 'limit' , $arrOther ) )
            {
                if( 'all' != $arrOther['limit'] )
                {
                    if( CLib::IsArrayWithKeys( $arrOther['limit'] ) )
                    {
                        $this->m_oDBLink->skip( intval( $arrOther['limit'][0] ) );
                        $this->m_oDBLink->take( intval( $arrOther['limit'][1] ) );
                    }
                    else
                    {
                        $this->m_oDBLink->take( intval( $arrOther['limit'] ) );
                    }
                }
            }

            if( array_key_exists( 'inc' , $arrOther ) )
            {
                if( CLib::IsArrayWithKeys( $arrOther['inc'] ) )
                {
                    $this->m_oDBLink->increment( $arrOther['inc'][0] , intval( $arrOther['inc'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->increment( $arrOther['inc'] );
                }

            }

            if( array_key_exists( 'dec' , $arrOther ) )
            {
                if( CLib::IsArrayWithKeys( $arrOther['dec'] ) )
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'][0] , intval( $arrOther['dec'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'] );
                }

            }
        }

        return $this->m_oDBLink;

    }

    /**
     * 拼查询条件返回数据(get)
     * @param string $id
     * @return array
     */
    private function _getDBGetData()
    {
        $result = array();

        $arrGet   = $this->_getArrDataTosKey('get');

        if( CLib::IsArrayWithKeys( $arrGet ) )
        {
            if( array_key_exists( 'max' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['max'] ) )
                {
                    $result['max'] = $this->m_oDBLink->max( $arrGet['max'] );
                }
            }

            if( array_key_exists( 'min' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['min'] ) )
                {
                    $result['min'] = $this->m_oDBLink->min( $arrGet['min'] );
                }
            }

            if( array_key_exists( 'avg' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['avg'] ) )
                {
                    $result['avg'] = $this->m_oDBLink->avg( $arrGet['avg'] );
                }
            }

            if( array_key_exists( 'sum' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['sum'] ) )
                {
                    $result['sum'] = $this->m_oDBLink->sum( $arrGet['sum'] );
                }
            }
        }

        return $result;
    }




    /**
     * 判断数组中下标是否存在
     * 返回json_decode数据
     * @param $sKey
     * @return array
     */
    private function _getArrDataTosKey( $sKey )
    {
        if ( ! CLib::IsExistingString( $sKey ) )
        {
            return [];
        }

        $arrRetn = array();

        if( CLib::IsArrayWithKeys( $this->m_arrInputData, [ $sKey ] ) )
        {
            $arrData = json_decode( $this->m_arrInputData[ $sKey ] , true );

            if( CLib::IsArrayWithKeys( $arrData ))
            {
                foreach ( $arrData as $sSKey => $sSVal )
                {
                    if( isset( $this->m_arrSelectAllow[$sKey] ) && ! in_array( $sSKey, $this->m_arrSelectAllow[$sKey] ) )
                    {
                        unset( $arrData[$sSKey] );
                    }
                }
                $arrRetn = $arrData;
            }
        }
        return $arrRetn;
    }


    /**
     * 返回格式化后的数据
     * @param $var
     * @return int|string
     */
    private function _getVarType( $Column = '', $var = '' )
    {
        $RVal = '';

        if( CofficeConst::$m_str_SetupTablesName == $this->m_sDBTableName )
        {
            $result = CofficeConst::$m_arr_SetupTablesColumnListType;
        }
        else
        {
            $result = app('db')->table( CofficeConst::$m_str_SetupTablesName )
                ->where('_Table',$this->m_sDBTableName)
                ->where('_Column', $Column)
                ->first();
        }

        if( !empty( $result ) )
        {
            // 类型验证
            if( in_array( $result[ '_Type' ] ,CofficeConst::$m_arr_StrData ) )
            {
                $RVal = strval( $var );
            }
            elseif( in_array( $result[ '_Type' ] ,CofficeConst::$m_arr_IntData ) )
            {
                $RVal = intval( $var );
            }
            else
            {
                $RVal = strval( $var );
            }
        }
        else
        {
            if( '_id' == $Column )
            {
                $RVal = intval( $var );
            }
            else
            {
                $RVal = strval( $var );
            }
        }

        return $RVal;
    }


    /**
     * Init
     */
    private function _Init()
    {
        // 数据过滤
        $this->m_arrInputData = app('request')->input();

        $this->m_arrSelectAllow  = @$this->m_arrInputData['selectAllow'];

        // 获取默认分页条数
        $this->m_itake      = CofficeConst::$m_itake;

        $this->m_sDBTableName  = CofficeAuth::GetInstance()->getUseClassName();

        $this->m_bUseMaster    = CofficeAuth::GetInstance()->isUseMasterKey();

        $this->m_sUseClassID   = CofficeAuth::GetInstance()->getUseClassID();

        $this->m_sUserObjectID = CofficeAuth::GetInstance()->getUserObjectID();

        $this->m_arrUserRole   = CofficeAuth::GetInstance()->getUserRole();

        $this->m_oDBLink       = app('db')->table( $this->m_sDBTableName );
    }


}