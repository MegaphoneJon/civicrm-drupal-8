<?PHP

namespace Drupal\civicrm\Commands;

//USE Drupal\civicrm\Controller\CivicrmController;
use Drush\Commands\sql\SqlCommands;
use Symfony\Component\Console\Input\InputInterface;
use Drush\Sql\SqlBase;

/**
 * Drush commandfile for CiviCRM
 * TODO: This will probably only contain the SQL commands.
 */
class CivicrmCommands extends SqlCommands {

  /**
   * Set to TRUE if CiviCRM is initialized.
   *
   * @var bool
   */
  private $init = FALSE;

  /**
   * A SqlBase object pointing to the CiviCRM database.
   *
   * @var Drush\Sql\SqlBase
   */
  private $dbObject;

  /**
   * An array of options that can be passed to  SqlBase::create to reference the CiviCRM database.
   *
   * @var array
   */
  private $civiDbOptions;

  /**
   * Print CiviCRM database connection details
   *
   * @command civicrm:sql-conf
   * @option show-passwords Show database password.
   */
  public function drush_civicrm_sqlconf() {
    $this->civicrm_dsn_init();
    $options = array_merge(['format' => 'yaml', 'all' => false, 'show-passwords' => false], $this->civiDbOptions);
    return $this->conf($options);
  }

  /**
   * A string for connecting to the CiviCRM DB.
   *
   * @command civicrm:sql-connect
   * @usage civicrm:sql-connect
   */
  public function drush_civicrm_sqlconnect() {
    $this->civicrm_dsn_init();
    
    return $this->connect($this->civiDbOptions);
  }

  /**
   * Exports the CiviCRM DB as SQL using mysqldump.
   *
   * @param string $name
   *   Argument provided to the drush command.
   *
   * @command civicrm:sql-dump
   * @options arr An option that takes multiple values.
   * @options  Whether or not an extra message should be displayed to the user.
   * @usage civicrm:sql-dump
   *   Display 'Hello Akanksha!' and a message.
   */
  public function drush_civicrm_sqldump($name, $options = ['msg' => FALSE]) {
  }

  /**
   * Open a SQL command-line interface using CiviCRM's credentials.
   *
   * @command civicrm:sql-cli
   * @aliases cvsqlc
   * @usage civicrm:sql-cli
   */
  public function drush_civicrm_sqlcli(InputInterface $input) {
    $this->civicrm_dsn_init();
    $this->cli($input, $this->civiDbOptions);
  }

  /**
   * CLI access to CiviCRM APIs. It can return pretty-printen json formatted
   * data.
   *
   * @param array $commands
   *
   * @command civicrm:api
   * @aliases cvapi
   * @option  uid Drupal uid that is used to check the privileges (type is
   *   number, default=1)
   * @option  in  Use arguments (standard, value args) or as json from the
   *   standard input JSON (not implemented yet)
   * @option  out
   * @usage drush civicrm:api contact.create first_name=John last_name=Doe
   *   contact_type=Individual
   *
   */
  public function drush_civicrm_api(array $commands, $options = [
    'uid' => 1,
    'in' => 'args',
    'out' => 'json',
  ]) {
    if (!in_array($options['out'], ['json', 'pretty'])) {
      throw new \RuntimeException("Unknown option --out={$options['out']}, must be json or pretty");
    }
    if (!in_array($options['in'], ['json', 'args'])) {
      throw new \RuntimeException("Unknown option --in={$options['in']}, must be args or pretty");
    }
    $DEFAULTS = ['version' => 3];
    $args = $commands;
    list($entity, $action) = explode('.', $args[0]);
    array_shift($args);
    \Drupal::service('civicrm')->initialize();
    $user = \Drupal\user\Entity\User::load($options['uid']);
    \CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Drupal', 'Individual');
    $params = $DEFAULTS;
    if ($options['in'] == 'json') {
      $json = stream_get_contents(STDIN);
      if (empty($json)) {
        $params = $DEFAULTS;
      }
      else {
        $params = array_merge($DEFAULTS, json_decode($json, TRUE));
      }
    }
    else {
      foreach ($args as $arg) {
        $matches = explode('=', $arg);
        $params[$matches[0]] = $matches[1];
      }
    }
    $result = civicrm_api($entity, $action, $params);
    return $options['out'] == 'pretty' ? print_r($result, TRUE) : json_encode($result, JSON_PRETTY_PRINT);
  }

  private function civicrm_dsn_init() {
    if (!$this->civicrm_init()) {
      return FALSE;
    }
    $this->civiDbOptions = [
      'database' => 'civicrm',
      'target' => 'default',
      'db-url' => CIVICRM_DSN,
    ];
    $this->dbObject = SqlBase::create($this->civiDbOptions);
  }

  private function civicrm_init() {
    // TODO: How to tell when the file is in sites/something_else?
    $civicrmSettingsFile = DRUPAL_ROOT . "/sites/default/civicrm.settings.php";
    if (!is_file($civicrmSettingsFile)) {
      $this->logger()->error("Could not locate civicrm.settings.php at $civicrmSettingsFile.");
      return FALSE;
    }
    include_once $civicrmSettingsFile;
    global $civicrm_root;
    if (!is_dir($civicrm_root)) {
      $this->logger()->error('Could not locate CiviCRM codebase. Make sure CiviCRM settings file has correct information.');
      return FALSE;
    }
    \CRM_Core_ClassLoader::singleton()->register();
    // Also initialize config object.
    \CRM_Core_Config::singleton();
    $this->init = TRUE;
    return $this->init;
  }

}
