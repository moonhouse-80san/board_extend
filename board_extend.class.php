<?php
    /**
     * @class  board Extend
     * @author xiso (xiso@xiso.co.kr)
     * @brief  board_extend module high class
     **/
	require_once(_XE_PATH_.'modules/board/board.class.php');
    class board_extend extends board {
	
        /**
         * @brief install the module
         **/
        function moduleInstall() {
			$oModuleController = &getController('module');
			$oModuleController->insertTrigger('display', 'board_extend', 'controller', 'triggerDisplay', 'before');
            return new BaseObject();
        }

        /**
         * @brief chgeck module method
         **/
        function checkUpdate() {
			$oModuleModel = &getModel('module');
			if(!$oModuleModel->getTrigger('display', 'board_extend', 'controller', 'triggerDisplay', 'before')) return true;
            return false;
        }

        /**
         * @brief update module
         **/
        function moduleUpdate() {
			$oModuleController = &getController('module');
			$oModuleModel = &getModel('module');
			if(!$oModuleModel->getTrigger('display', 'board_extend', 'controller', 'triggerDisplay', 'before'));
				$oModuleController->insertTrigger('display', 'board_extend', 'controller', 'triggerDisplay', 'before');
            return new BaseObject(0, 'success_updated');
        }

		function moduleUninstall() {
			return new BaseObject();
		}

        /**
         * @brief re-generate the cache files
         **/
        function recompileCache() {
        }

    }
?>