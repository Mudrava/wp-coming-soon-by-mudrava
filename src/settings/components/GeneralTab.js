/**
 * Settings – General Tab
 *
 * Controls for enabling Coming Soon mode, SEO fields (page title,
 * meta description), Retry-After header (human-friendly hours/days),
 * and optional launch date with toggle.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    PanelRow,
    ToggleControl,
    TextControl,
    TextareaControl,
    SelectControl,
    Button,
} from '@wordpress/components';
import { useState, useMemo, useCallback } from '@wordpress/element';

/**
 * Convert seconds to a human-friendly display value + unit.
 *
 * @param {number} seconds
 * @return {{ value: number, unit: string }}
 */
function secondsToDisplay(seconds) {
    if (seconds >= 86400 && seconds % 86400 === 0) {
        return { value: seconds / 86400, unit: 'days' };
    }
    return { value: Math.round(seconds / 3600), unit: 'hours' };
}

export default function GeneralTab({ settings, updateSetting }) {
    const retryDisplay = useMemo(
        () => secondsToDisplay(settings.retry_after || 86400),
        [settings.retry_after]
    );

    const [retryValue, setRetryValue] = useState(String(retryDisplay.value));
    const [retryUnit, setRetryUnit] = useState(retryDisplay.unit);

    /**
     * Update retry_after in seconds when value or unit changes.
     */
    const handleRetryChange = (newValue, newUnit) => {
        const num = parseInt(newValue, 10);
        if (isNaN(num) || num < 1) return;

        const multiplier = newUnit === 'days' ? 86400 : 3600;
        const seconds = Math.min(num * multiplier, 2592000); // Max 30 days
        updateSetting('retry_after', seconds);
    };

    return (
        <div className="mudrava-cs-settings__tab-content">
            <PanelBody title={__('Coming Soon Mode', 'wp-coming-soon-by-mudrava')}>
                <PanelRow>
                    <ToggleControl
                        label={__('Enable Coming Soon Mode', 'wp-coming-soon-by-mudrava')}
                        help={
                            settings.enabled
                                ? __('Your site is currently showing the Coming Soon page.', 'wp-coming-soon-by-mudrava')
                                : __('Your site is publicly accessible.', 'wp-coming-soon-by-mudrava')
                        }
                        checked={!!settings.enabled}
                        onChange={(val) => updateSetting('enabled', val)}
                    />
                </PanelRow>
            </PanelBody>

            <PanelBody title={__('SEO', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <TextControl
                    label={__('Page Title', 'wp-coming-soon-by-mudrava')}
                    value={settings.page_title || ''}
                    onChange={(val) => updateSetting('page_title', val)}
                />
                <TextareaControl
                    label={__('Meta Description', 'wp-coming-soon-by-mudrava')}
                    value={settings.meta_description || ''}
                    onChange={(val) => updateSetting('meta_description', val)}
                    rows={3}
                />
            </PanelBody>

            <PanelBody title={__('Retry-After Header', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <p className="components-base-control__help" style={{ marginTop: 0 }}>
                    {__('Tells search engines when to retry indexing. Sent with the 503 status code.', 'wp-coming-soon-by-mudrava')}
                </p>
                <div className="mudrava-cs-settings__retry-row">
                    <TextControl
                        type="number"
                        min="1"
                        value={retryValue}
                        onChange={(val) => {
                            setRetryValue(val);
                            handleRetryChange(val, retryUnit);
                        }}
                    />
                    <SelectControl
                        value={retryUnit}
                        options={[
                            { label: __('Hours', 'wp-coming-soon-by-mudrava'), value: 'hours' },
                            { label: __('Days', 'wp-coming-soon-by-mudrava'), value: 'days' },
                        ]}
                        onChange={(val) => {
                            setRetryUnit(val);
                            handleRetryChange(retryValue, val);
                        }}
                    />
                </div>
            </PanelBody>

            <PanelBody title={__('Launch Date', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <PanelRow>
                    <ToggleControl
                        label={__('Use Launch Date', 'wp-coming-soon-by-mudrava')}
                        help={__('When enabled, Coming Soon mode will automatically deactivate after the launch date passes.', 'wp-coming-soon-by-mudrava')}
                        checked={!!settings.use_launch_date}
                        onChange={(val) => updateSetting('use_launch_date', val)}
                    />
                </PanelRow>

                {settings.use_launch_date && (
                    <div className="mudrava-cs-settings__launch-date">
                        <TextControl
                            label={__('Launch Date & Time', 'wp-coming-soon-by-mudrava')}
                            type="datetime-local"
                            value={settings.launch_date ? settings.launch_date.slice(0, 16) : ''}
                            onChange={(val) => updateSetting('launch_date', val ? val + ':00' : '')}
                        />
                        {settings.launch_date && (
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => updateSetting('launch_date', '')}
                            >
                                {__('Clear date', 'wp-coming-soon-by-mudrava')}
                            </Button>
                        )}
                    </div>
                )}
            </PanelBody>
        </div>
    );
}
