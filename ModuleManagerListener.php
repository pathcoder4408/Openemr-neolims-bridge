<?php

use OpenEMR\Core\AbstractModuleActionListener;

class ModuleManagerListener extends AbstractModuleActionListener
{
    public static function getModuleNamespace(): string
    {
        return 'OpenEMR\\Modules\\NeoLimsBridge\\';
    }

    public static function initListenerSelf(): ModuleManagerListener
    {
        return new self();
    }

    public function moduleManagerAction(
        $methodName,
        $modId,
        string $currentActionStatus = 'Success'
    ): string {
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($modId, $currentActionStatus);
        }
        return $currentActionStatus;
    }

    private function install($modId, $status): string
    {
        self::setModuleState($modId, '0', '1');
        return $status;
    }

    private function enable($modId, $status): string
    {
        self::setModuleState($modId, '1', '0');
        return $status;
    }

    private function disable($modId, $status): string
    {
        self::setModuleState($modId, '0', '1');
        return $status;
    }

    private static function setModuleState($modId, $active, $uiActive)
    {
        return sqlQuery(
            "UPDATE modules
                SET mod_active = ?, mod_ui_active = ?
              WHERE mod_id = ? OR mod_directory = ?",
            [$active, $uiActive, $modId, $modId]
        );
    }
}
