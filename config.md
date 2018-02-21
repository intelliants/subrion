# Configuration

Subrion CMS configuration options can be easily accessed using iaCore object methods.

**$iaCore->get()** - _get current value for a specific config key_
```php
echo $iaCore->get('sitename'); # prints current sitename
```
As of Subrion CMS 4.2+ this method accepts 4 params:

```php
/**
 * Get the specified configuration value
 *
 * @param string $key configuration key
 * @param bool|false $default default value
 * @param bool|true $custom custom config flag
 * @param bool|false $db true gets from database directly
 *
 * @return string
 */
public function get($key, $default = false, $custom = true, $db = false)
{
    if ($custom && isset($this->_customConfig[$key])) {
        return $this->_customConfig[$key];
    }
    $result = $default;
    if ($db) {
        $value = $this->factory('config')->get($key);
        if (false !== $value) {
            $result = $value;
        }
    } else {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        }
    }
    $this->_config[$key] = $result;

    return $result;
}
```

Sometimes you might need to change default config values during script runtime. And this can be achieved using another function.

**$iaCore->set()** - _set new value for a specific config key_
```php
$iaCore->set('sitename', 'New Site Name'); # sets new sitename during script execution
```
As of Subrion CMS 4.2+ this method accepts 3 params:

```php
/**
 * Set a given configuration value
 *
 * @param string $key configuration key
 * @param string $value configuration value
 * @param bool|false $permanent saves permanently in db
 *
 * @return bool
 */
public function set($key, $value, $permanent = false)
{
    if ($permanent && !is_scalar($value)) {
        trigger_error(__METHOD__ . '() Could not write a non-scalar value to the database.', E_USER_ERROR);
    }

    $result = true;
    $this->_config[$key] = $value;

    if ($permanent) {
        $result = $this->factory('config')->set($key, $value);

        $this->iaCache->clearConfigCache();
    }

    return $result;
}
```