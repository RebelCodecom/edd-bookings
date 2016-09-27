<?php

namespace Aventura\Edd\Bookings\Settings;

use \Aventura\Edd\Bookings\Plugin;
use \Aventura\Edd\Bookings\Settings\Database\DatabaseInterface;
use \Aventura\Edd\Bookings\Settings\Database\Record\Record;
use \Aventura\Edd\Bookings\Settings\Database\Record\RecordInterface;
use \Aventura\Edd\Bookings\Settings\Database\Record\SubRecord;
use \Aventura\Edd\Bookings\Settings\Option\OptionInterface;
use \Aventura\Edd\Bookings\Settings\Section\SectionInterface;
use \InvalidArgumentException;

/**
 * Standard implementation of a settings controller for EDD extension settings.
 *
 * @author Miguel Muscat <miguelmuscat93@gmail.com>
 */
class Settings extends AbstractSettings
{

    const RECORD_KEY = 'eddbk';

    protected $record;

    /**
     * Constructs a new instance.
     *
     * @param Plugin $plugin The parent plugin instance.
     * @param DatabaseInterface $database The database controller instance.
     */
    public function __construct(Plugin $plugin, DatabaseInterface $database)
    {
        parent::__construct($plugin);
        $this->setDatabase($database)
            ->resetSections();
        // Set the record
        $eddSettingsRecord = new Record($this->getDatabase(), 'edd_settings');
        $this->setRecord(new SubRecord($eddSettingsRecord, 'eddbk'));
    }

    /**
     * Gets the settings DB record.
     *
     * @return RecordInterface The record instance.
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Sets the settings DB record.
     *
     * @param RecordInterface $record The record instance.
     * @return Settings This instance.
     */
    public function setRecord(RecordInterface $record)
    {
        $this->record = $record;
        return $this;
    }

    /**
     * Adds a section to this instance.
     *
     * @param SectionInterface $section The section instance to add.
     * @return static This instance.
     */
    public function addSection(SectionInterface $section)
    {
        // Set section's record as a subrecord of this instance's
        $sectionRecord = new SubRecord($this->getRecord(), $section->getId());
        $section->setRecord($sectionRecord);
        // Add to list
        $this->sections[$section->getId()] = $section;

        return $this;
    }

    /**
     * Adds an array of sections to this instance.
     *
     * @param SectionInterface[] $sections An array of section instances. Non-section array entries will be ignored.
     * @return static This instance.
     */
    public function addSections(array $sections)
    {
        foreach ($sections as $section) {
            if ($section instanceof SectionInterface) {
                $this->addSection($section);
            }
        }

        return $this;
    }

    /**
     * Gets a section with a specific ID.
     *
     * @param string $id The ID of the section to return.
     * @return SectionInterface|null The section instance or null if no section with the given ID was found.
     */
    public function getSection($id)
    {
        return $this->hasSection($id)
            ? $this->sections[$id]
            : null;
    }

    /**
     * Checks if this instance has a section with a specific ID.
     *
     * @param string $id The ID of the section to search for.
     * @return boolean True if a section with the given ID exists, false if not.
     */
    public function hasSection($id)
    {
        return isset($this->sections[$id]);
    }

    /**
     * Removes a section with a specific ID.
     *
     * @param string $id The ID of the section to remove.
     * @return static This instance.
     */
    public function removeSection($id)
    {
        unset($this->sections[$id]);

        return $this;
    }

    /**
     * Removes all sections from this instance.
     *
     * @return static This instance.
     */
    public function resetSections()
    {
        $this->sections = array();

        return $this;
    }

    /**
     * Gets the label of the EDD extensions tab.
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Bookings', 'eddbk');
    }

    /**
     * Loads the settings from the config file.
     */
    public function loadConfig()
    {
        $config = $this->getPlugin()->loadConfigFile('settings');
        if (is_array($config)) {
            $this->addSections($config);
        }
    }

    public function registerEddExtensionsTab($tabs)
    {
        $tabs[static::RECORD_KEY] = $this->getTabLabel();
        return $tabs;
    }

    /**
     * Registers the settings with EDD.
     */
    public function registerSettings($settings)
    {
        $toRegister = array();
        foreach ($this->getSections() as $section) {
            // Register a dummy option for the section itself
            $toRegister[$section->getId()] = $this->prepareEddSetting($section);
            // Register an option for each option in the section
            foreach ($section->getOptions() as $option) {
                $prefix = sprintf('%s.', $section->getId());
                $settingData = $this->prepareEddSetting($option, $prefix);
                $settingId = $settingData['id'];
                $toRegister[$settingId] = $settingData;
            }
        }
        // If EDD is at version 2.5 or later...
        if (version_compare(EDD_VERSION, 2.5, '>=')) {
            // Use the previously noted array key as an array key again and next your settings
            $toRegister = array(static::RECORD_KEY => $toRegister);
        }
        $allSettings = array_merge($settings, $toRegister);
        return $allSettings;
    }

    protected function prepareEddSetting($arg, $idPrefix = '')
    {
        if (!($arg instanceof SectionInterface) && !($arg instanceof OptionInterface)) {
            throw new InvalidArgumentException('Expected argument to be a section or an option.');
        }

        $id = $idPrefix . $arg->getId();

        if ($arg instanceof SectionInterface) {
            $name = sprintf('<strong>%s</strong>', $arg->getName());
            $type = 'header';
        } else {
            $name = $arg->getName();
            $desc = $arg->getDescription();
            $type = 'hook';
            // Set up the hook callback
            $action = sprintf('edd_%s', $id);
            $callback = array($this, 'renderOption');
            if (!has_action($action, $callback)) {
                add_action($action, $callback);
            }
        }
        $data = compact('id', 'name', 'desc', 'type');
        $data['salami'] = sprintf('edd_%s', $id);
        return $data;
    }

    public function renderOption($args)
    {
        $id = $args['id'];

        $parts = explode('.', $id, 2);
        if (count($parts) !== 2) {
            return;
        }
        list($sectionId, $optionId) = $parts;
        $section = $this->getSection($sectionId);
        if (is_null($section)) {
            return;
        }
        $option = $section->getOption($optionId);
        echo $this->getPlugin()->renderView($option->getView(), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function hook()
    {
        $this->loadConfig();
        $this->getPlugin()->getHookManager()
            ->addFilter('edd_settings_sections_extensions', $this, 'registerEddExtensionsTab')
            ->addFilter('edd_settings_extensions', $this, 'registerSettings')
        ;
    }

}
