<?PHP

namespace Drupal\civicrm\Commands;

//USE Drupal\civicrm\Controller\CivicrmController;
use Drush\Commands\DrushCommands;
use Drush\Commands\sql\SqlCommands;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\Core\Database\Database;
use Drush\Sql\SqlBase;

/**
 * Drush commandfile for CiviCRM
 */
class CivicrmCommands extends DrushCommands {

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
   * A string for connecting to the CiviCRM DB.
   *
   * @command civicrm:sql-connect
   * @usage civicrm:sql-connect
   */
  public function drush_civicrm_sqlconnect() {
    $this->civicrm_dsn_init();
    // SqlCommands::connect($this->civicrmConnectionInfo);
    return $this->dbObject->connect(FALSE);
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
    if ($options['msg']) {
      $this->output()->writeln('Hello ' . $name . '! This is your first Drush 9 command.');
    }
    else {
      $this->output()->writeln('Hello ' . $name . '!');
    }
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
$temp = new sqlCommands;
    $temp->cli($input);
  }

  private function civicrm_dsn_init() {
    if (!$this->civicrm_init()) {
      return FALSE;
    }
    $options = [
      'database' => 'civicrm',
      'target' => 'default',
      'db-url' => CIVICRM_DSN,
    ];
    $this->dbObject = SqlBase::create($options);
/*
    Database::addConnectionInfo('civicrm', 'default', Database::convertDbUrlToConnectionInfo(CIVICRM_DSN, DRUPAL_ROOT));
    Database::setActiveConnection('civicrm');
    $this->civicrmConnectionInfo['civicrm'] = Database::getConnectionInfo('civicrm');
*/
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
