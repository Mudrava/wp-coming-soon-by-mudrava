/**
 * Settings App – Root Component
 *
 * Manages plugin settings state, REST API communication, and renders
 * a tabbed interface with General, Access, and Advanced panels.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { TabPanel, Button, Snackbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import GeneralTab from './components/GeneralTab';
import AccessTab from './components/AccessTab';
import AdvancedTab from './components/AdvancedTab';

const TABS = [
    {
        name: 'general',
        title: __('General', 'wp-coming-soon-by-mudrava'),
        className: 'mudrava-cs-tab',
    },
    {
        name: 'access',
        title: __('Access', 'wp-coming-soon-by-mudrava'),
        className: 'mudrava-cs-tab',
    },
    {
        name: 'advanced',
        title: __('Advanced', 'wp-coming-soon-by-mudrava'),
        className: 'mudrava-cs-tab',
    },
];

export default function App() {
    const [settings, setSettings] = useState(null);
    const [saving, setSaving] = useState(false);
    const [notice, setNotice] = useState(null);

    /* Fetch settings on mount. */
    useEffect(() => {
        apiFetch({
            path: 'mudrava/coming-soon/v1/settings',
        }).then((data) => {
            setSettings(data);
        }).catch((err) => {
            setNotice({
                status: 'error',
                content: err.message || __('Failed to load settings.', 'wp-coming-soon-by-mudrava'),
            });
        });
    }, []);

    /**
     * Update a single setting value.
     */
    const updateSetting = useCallback((key, value) => {
        setSettings((prev) => ({
            ...prev,
            [key]: value,
        }));
    }, []);

    /**
     * Save settings via REST API.
     */
    const handleSave = useCallback(async () => {
        if (!settings) return;

        setSaving(true);
        setNotice(null);

        try {
            const updated = await apiFetch({
                path: 'mudrava/coming-soon/v1/settings',
                method: 'POST',
                data: settings,
            });

            setSettings(updated);
            setNotice({
                status: 'success',
                content: __('Settings saved.', 'wp-coming-soon-by-mudrava'),
            });
        } catch (err) {
            setNotice({
                status: 'error',
                content: err.message || __('Failed to save settings.', 'wp-coming-soon-by-mudrava'),
            });
        } finally {
            setSaving(false);
        }
    }, [settings]);

    if (!settings) {
        return (
            <div className="mudrava-cs-settings">
                <p>{__('Loading…', 'wp-coming-soon-by-mudrava')}</p>
            </div>
        );
    }

    const { editPageUrl, version } = window.mudravaComingSoon || {};

    return (
        <div className="mudrava-cs-settings">
            <div className="mudrava-cs-settings__header">
                <div className="mudrava-cs-settings__header-left">
                    <h1>{__('Coming Soon by Mudrava', 'wp-coming-soon-by-mudrava')}</h1>
                    {version && (
                        <span className="mudrava-cs-settings__version">v{version}</span>
                    )}
                </div>
                <div className="mudrava-cs-settings__header-right">
                    {editPageUrl && (
                        <Button
                            href={editPageUrl}
                            variant="secondary"
                            target="_blank"
                        >
                            {__('Edit Page Content', 'wp-coming-soon-by-mudrava')}
                        </Button>
                    )}
                    <Button
                        variant="primary"
                        onClick={handleSave}
                        isBusy={saving}
                        disabled={saving}
                    >
                        {saving
                            ? __('Saving…', 'wp-coming-soon-by-mudrava')
                            : __('Save Settings', 'wp-coming-soon-by-mudrava')}
                    </Button>
                </div>
            </div>

            <TabPanel tabs={TABS}>
                {(tab) => {
                    switch (tab.name) {
                        case 'general':
                            return (
                                <GeneralTab
                                    settings={settings}
                                    updateSetting={updateSetting}
                                />
                            );
                        case 'access':
                            return (
                                <AccessTab
                                    settings={settings}
                                    updateSetting={updateSetting}
                                />
                            );
                        case 'advanced':
                            return (
                                <AdvancedTab
                                    settings={settings}
                                    updateSetting={updateSetting}
                                />
                            );
                        default:
                            return null;
                    }
                }}
            </TabPanel>

            {notice && (
                <Snackbar
                    status={notice.status}
                    onRemove={() => setNotice(null)}
                >
                    {notice.content}
                </Snackbar>
            )}
        </div>
    );
}
