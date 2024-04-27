<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UlbMaster extends Model
{
    use HasFactory;
    protected $connection= 'pgsql_master';

    public function GetUlbsWithWard($user_id, $Ward)
    {
        if (isset($user_id)) {
            $ulbs = Self::select('ulb_ward_masters.ulb_id', 'ulb_masters.ulb_name')
                    ->join('ulb_ward_masters', 'ulb_ward_masters.ulb_id', '=', 'ulb_masters.id')
                    ->join('wf_ward_users', 'wf_ward_users.ward_id', '=', 'ulb_ward_masters.id')
                    ->where('wf_ward_users.user_id', $user_id)
                    ->where('wf_ward_users.is_suspended', false)
                    ->groupBy(['ulb_masters.ulb_name', 'ulb_ward_masters.ulb_id'])
                    ->get();

            $ulbarr = array();
            if ($ulbs) {
                foreach ($ulbs as $u) {
                    $val['ulbId'] = $u->ulb_id;
                    $val['ulbName'] = $u->ulb_name;
                    //$val['ulb'] = $u->ulb;
                    //$val['wards'] = $this->GetAllWard($u->ulb_id, $user_id, $Ward);
                    $ulbarr[] = $val;
                }
            }
            
            return $ulbarr;
        }
    }

    static function GetAllWard($ulb_id, $user_id, $Ward)
    {
        if (isset($user_id)) {
            $sql = "SELECT ward_id FROM tbl_user_ward w
            JOIN tbl_ulb_list u on w.ulb_id=u.id
            WHERE user_id=" . $user_id . " and ulb_id=" . $ulb_id . " and w.stts=1 group by ward_id";

            $ulbs = Self::select('ward_id')
                ->join('wf_ward_users', 'wf_ward_users.ward_id', '=', 'ulb_ward_masters.id')
                ->where('wf_ward_users.user_id', $user_id)
                ->where('wf_ward_users.is_suspended', false)
                ->groupBy(['ulb_masters.ulb_name', 'ulb_ward_masters.ulb_id'])
                ->get();

            $ulbs = Self::select($sql);
            $wardarr = array();
            if ($ulbs) {
                foreach ($ulbs as $u) {
                    $getward = $Ward->where('id', $u->ward_id)
                        ->first();
                    if ($getward)
                        $wardarr[] = $getward->name;
                }
            }
            return $wardarr;
        }
    }
}
