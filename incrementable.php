<?php 
/**
 * Incrementable Behavior Behavior class file.
 *
 * @filesource
 * @author Tim Koschuetzki, Debuggable Ltd., http://debuggable.com
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app
 * @subpackage app.models.behaviors
 */
/**
 * Model behavior to support an uuid-independent unique id for a model object across several tables
 *
 * @package app
 * @subpackage app.models.behaviors
 */
class IncrementableBehavior extends ModelBehavior {
/**
 * Contain settings indexed by model name.
 *
 * @var array
 * @access private
 */
	var $__settings = array();
/**
 * Initiate behavior for the model using settings.
 *
 * @param object $Model Model using the behavior
 * @param array $settings Settings to override for model.
 * @access public
 */
	function setup(&$Model, $settings = array()) {
		$defaults = array(
			'field' => 'unique_id',
			'length' => '8',
			'across' => array($Model->alias)
		);

		if (!isset($this->__settings[$Model->alias])) {
			$this->__settings[$Model->alias] = $defaults;
		}
		$this->__settings[$Model->alias] = am(
			$this->__settings[$Model->alias],
			ife(is_array($settings), $settings, array())
		);
	}
/**
 * undocumented function
 *
 * @param string $Model 
 * @param string $created 
 * @return void
 * @author Tim Koschuetzki
 */
	function afterSave($Model, $created) {
		if ($created) {
			extract($this->__settings[$Model->alias]);
			$key = $Model->lookup(array('id' => $Model->id), $field, false);
			if (!empty($key)) {
				return $key;
			}

			$Model->set(array('id' => $Model->id, $field => $this->key($Model)));
			return $Model->save(null, array('callbacks' => false));
		}
		return true;
	}
/**
 * undocumented function
 *
 * @param string $Model
 * @param string $id
 * @return void
 * @author Tim Koschuetzki
 */
	function key($Model) {
		extract($this->__settings[$Model->alias]);

		$usedKeys = array();
		foreach ($across as $model) {
			$UseModel = ClassRegistry::init($model);

			$conditions = array();
			if (!empty($UseModel->id)) {
				$conditions[$UseModel->alias . '.' . $UseModel->primaryKey . ' <>'] = $UseModel->id;
			}
			$result = $UseModel->find('all', array(
				'conditions' => $conditions,
				'fields' => array($field),
				'recursive' => -1
			));

			if (!empty($result)) {
				$usedKeys = am($usedKeys, Set::extract('/' . $UseModel->alias . '/' . $field, $result));
			}
		}

		App::import('Core', 'Security');
		while (true) {
			$key = substr(Security::generateAuthKey(), 0, $length);
			if (!in_array($key, $usedKeys)) {
				break;
			}
		}

		// force that we are on the white list of fields to be saved
		if (!empty($Model->whitelist) && !in_array($field, $Model->whitelist)) {
			$Model->whitelist[] = $field;
		}

		return $key;
	}
}
?>