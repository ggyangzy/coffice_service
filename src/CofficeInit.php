<?php
namespace yzyblog\coffice_service;

class CofficeInit
{
    /**
     * 创建/初始化 应用
     * @param string $dbs
     * @param $arrOutPutData
     * @param $sErroeMsg
     * @return int
     */
    public static function initAppList( $dbs = 'coffice_manager', & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_EXEC_ERROR;
        $sErroeMsg = CofficeConst::ZH_ERROR_ACCESS_EXEC_ERROR;

        $nCall = false;

        $arrInit = [
            '_id'        => Coffice::getRandomID(),
            'app_key'    => Coffice::getRandomID(),
            'master_key' => Coffice::getRandomID().Coffice::getRandomID(),
            'ACL'        => [
                '*' => [
                    'read'  => true,
                    'write' => true
                ]
            ]
        ];

        if( CofficeAuth::GetInstance()->initialize() )
        {
            if ( ! app('db')->connection('mongodb_coffice')->table('app_list')->where('dbs',$dbs)->first() )
            {
                $arrInit['dbs'] = $dbs;
                $nCall = app('db')->connection('mongodb_coffice')->table('app_list')->insert( $arrInit );
            }
        }
        else
        {
            if ( ! app('db')->connection('mongodb_coffice')->table('app_list')->first() )
            {
                $arrInit['dbs'] = 'coffice_manager';
                $nCall = app('db')->connection('mongodb_coffice')->table('app_list')->insert( $arrInit );
            }
        }

        if( $nCall )
        {
            $arrDefaultTables   = array();
            $arrDefaultTables[] = array_merge( [ '_id' => Coffice::getRandomID(), 'className' => CofficeConst::$m_str_Class_User ], CofficeConst::$m_arr_DefaultACL );
            $arrDefaultTables[] = array_merge( [ '_id' => Coffice::getRandomID(), 'className' => CofficeConst::$m_str_Class_Role ], CofficeConst::$m_arr_DefaultACL );
            $arrDefaultTables[] = array_merge( [ '_id' => Coffice::getRandomID(), 'className' => CofficeConst::$m_str_Class_Relation ], CofficeConst::$m_arr_DefaultACL );

            $arrUserColumnList = array_map( array( __CLASS__, "setClassObjectId"), CofficeConst::$m_arr_UserColumnList );
            $arrRoleColumnList = array_map( array( __CLASS__, "setClassObjectId"), CofficeConst::$m_arr_RoleColumnList );
            $arrRelationColumnList = array_map( array( __CLASS__, "setClassObjectId"), CofficeConst::$m_arr_RelationColumnList );
            $arrDefaultTablesColumnList = array_merge( $arrUserColumnList, $arrRoleColumnList, $arrRelationColumnList );

            app('config')->set('database.connections.mongodb_account.database', $arrInit['dbs'] );
            app('db')->table( CofficeConst::$m_str_SetupTablesName )->insert( $arrDefaultTables );
            app('db')->table( CofficeConst::$m_str_SetupTablesColumn )->insert( $arrDefaultTablesColumnList );

            $nRet = CofficeConst::ERROR_SUCCESS;
            $sErroeMsg = CofficeConst::ZH_ERROR_SUCCESS;
            $arrOutPutData = [
                'dbs'       => $arrInit['dbs'],
                'app_id'    => $arrInit['_id'],
                'app_key'   => $arrInit['app_key'],
                'master_key'=> $arrInit['master_key'],
            ];
        }

        return $nRet;
    }

    /**
     * 替换_id主键
     * @param $arr
     * @return array
     */
    private static function setClassObjectId( $arr )
    {
        $arr = array_merge( [ '_id' => Coffice::getRandomID() ], $arr );
        return $arr;
    }
}