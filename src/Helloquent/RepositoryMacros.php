<?php namespace WhiteFrame\Statistics\Helloquent;

use Illuminate\Database\Eloquent\Builder;
use WhiteFrame\Helloquent\Repository;
use WhiteFrame\Statistics\Statistics;

/**
 * Class RepositoryMacros
 * @package WhiteFrame\Dynatable
 */
class RepositoryMacros extends \WhiteFrame\Helloquent\Repository
{
	public function register()
	{
		$this->registerScopeToStatistics();
	}

	public function registerScopeToStatistics()
	{
		Repository::macro('scopeToStatistics', function(Builder $query, $options = []) {
			return Statistics::of($query, $options);
		});
	}
}