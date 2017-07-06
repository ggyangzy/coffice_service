<?php
namespace yzyblog\coffice_service;

class CofficeInit
{
    public static function initAppList( $dbs = 'coffice_manager', & $arrOutPutData, & $sErroeMsg )
    {
        $nRet = CofficeConst::ERROR_ACCESS_CLASS_NO_ALLOW;

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

        if( CAuth::GetInstance()->initialize() )
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
            $setupTablesName = [
                '_id'       => Coffice::getRandomID(),
                'className' => CofficeConst::$m_str_Class_User,
                'ACL'       => [
                    '*' => [
                        'read'  => true,
                        'write' => true,
                    ]
                ],
                'columnACL' => [
                    '*' => [
                        'read'  => true,
                        'write' => true,
                    ]
                ]
            ];

            foreach ( CofficeConst::$m_arr_UserColumnList as $k => & $val )
            {
                $val['_id'] = Coffice::getRandomID();
            }

            app('config')->set('database.connections.mongodb_account.database', $arrInit['dbs'] );
            app('db')->table( CofficeConst::$m_str_SetupTablesName )->insert( $setupTablesName );
            app('db')->table( CofficeConst::$m_str_SetupTablesColumn )->insert( CofficeConst::$m_arr_UserColumnList );

            $nRet = CofficeConst::ERROR_SUCCESS;
            $arrOutPutData = [
                'dbs'       => $arrInit['dbs'],
                'app_id'    => $arrInit['_id'],
                'app_key'   => $arrInit['app_key'],
                'master_key'=> $arrInit['master_key'],
            ];
        }
        else
        {
            $sErroeMsg = '操作失败';
        }

        return $nRet;
    }
}
