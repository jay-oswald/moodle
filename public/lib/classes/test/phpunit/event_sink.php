<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core\test\phpunit;

/**
 * Event redirection sink.
 *
 * @package    core
 * @category   test
 * @copyright  2013 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_sink {
    /** @var \core\event\base[] array of events */
    protected $events = [];

    /**
     * Stop event redirection.
     *
     * Use if you do not want event redirected any more.
     */
    public function close() {
        phpunit_util::stop_event_redirection();
    }

    /**
     * To be called from phpunit_util only!
     *
     * @param \core\event\base $event record from event_read table
     */
    public function add_event(\core\event\base $event) {
        /* Number events from 0. */
        $this->events[] = $event;
    }

    /**
     * Returns all redirected events.
     *
     * The instances are records form the event_read table.
     * The array indexes are numbered from 0 and the order is matching
     * the creation of events.
     *
     * @return \core\event\base[]
     */
    public function get_events() {
        return $this->events;
    }

    /**
     * Return number of events redirected to this sink.
     *
     * @return int
     */
    public function count() {
        return count($this->events);
    }

    /**
     * Removes all previously stored events.
     */
    public function clear() {
        $this->events = [];
    }

    /**
     * Returns the position of the first event of the given type in the list of events.
     *
     * @param string $event The class name of the event to search for.
     * @return int|null The position of the event in the list, or null if not
     */
    public function event_position(string $event): ?int {
        foreach ($this->events as $i => $e) {
            if ($e instanceof $event) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Returns the first of a given event type, or the first event if no type is given.
     *
     * @param string|null $event The class name of the event to search for, or null to return the first event.
     * @return \core\event\base|null The event, or null if not found
     */
    public function first_event(?string $event = null): ?\core\event\base {
        if (!$event) {
            return reset($this->events) ?: null;
        }

        $position = $this->event_position($event);
        if ($position !== null) {
            return $this->events[$position];
        }
        return null;
    }
}

// Alias this class to the old name.
// This file will be autoloaded by the legacyclasses autoload system.
// In future all uses of this class will be corrected and the legacy references will be removed.
class_alias(event_sink::class, \phpunit_event_sink::class);
