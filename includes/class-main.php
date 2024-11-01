<?php

class SWPF_Super_Rollback {
    public function __construct() {
        new SWPF_Super_Rollback_Assets();
        new SWPF_Super_Rollback_Ajax();
        new SWPF_Super_Rollback_Backup();
        new SWPF_Super_Rollback_Hooks();
    }
}