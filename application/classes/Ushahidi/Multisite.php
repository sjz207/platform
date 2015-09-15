<?php defined('SYSPATH') or die('No direct script access');

/**
 * Ushahidi Multisite
 *
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi\Application
 * @copyright  2014 Ushahidi
 * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 */
use League\Url\Url;

class Ushahidi_Multisite
{
	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function getDbConfig($host = NULL) {
		// Load the default domain
		$domain = Kohana::$config->load('multisite.domain');

		// If no host passed in, check the for HOST in environment
		if (!$host) {
			$host = getenv('HOST');
		}
		// If we still don't have a host
		if (! $host) {
			// .. parse the current URL
			$url = Url::createFromServer($_SERVER);
			// .. and grab the host
			$host = $url->getHost()->toUnicode();
		}

		// If $domain is set and we're at a subdomain of $domain..
		if ($domain AND substr_compare($host, $domain, strlen($domain) * -1) !== FALSE) {
			// .. grab just the subdomain
			$subdomain = substr($host, 0, (strlen($domain) * -1) -1);
		} else {
			// .. otherwise grab the whole domain
			$domain = $host;
			$subdomain = '';
		}

		// .. and find the current deployment credentials
		$result = DB::select()->from('deployments')
			->where('subdomain', '=', $subdomain)
			->where('domain', '=', $domain)
			->limit(1)
			->offset(0)
			// @todo filter only active deployments?
			->execute($this->db);
		$deployment = $result->current();

		// No deployment? throw a 404
		if (! count($deployment)) {
			throw new HTTP_Exception_404("Deployment not found");
		}

		// Set new database config
		$config = Kohana::$config->load('database')->default;
		$config['connection'] = [
			'hostname'   => $deployment['db_host'],
			'database'   => $deployment['db_name'],
			'username'   => $deployment['db_username'],
			'password'   => $deployment['db_password'],
			'persistent' => $config['connection']['persistent'],
		];

		return $config;
	}
}
