/**
 * Countdown Block – Editor Component
 *
 * Provides a live preview of the countdown timer in the block editor with
 * controls for configuring the target date, visible units, labels, and
 * the expired message.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    TextControl,
} from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * Calculate remaining time until the target date.
 *
 * @param {string} targetDate ISO date string.
 * @return {object|null} Remaining days, hours, minutes, seconds or null if expired.
 */
function getTimeRemaining(targetDate) {
    if (!targetDate) return null;

    const total = new Date(targetDate).getTime() - Date.now();

    if (total <= 0) return null;

    return {
        days: Math.floor(total / (1000 * 60 * 60 * 24)),
        hours: Math.floor((total / (1000 * 60 * 60)) % 24),
        minutes: Math.floor((total / (1000 * 60)) % 60),
        seconds: Math.floor((total / 1000) % 60),
    };
}

/**
 * Pad a number with a leading zero.
 *
 * @param {number} n Number to pad.
 * @return {string}
 */
function padZero(n) {
    return String(n).padStart(2, '0');
}

export default function Edit({ attributes, setAttributes }) {
    const {
        targetDate,
        showDays,
        showHours,
        showMinutes,
        showSeconds,
        labelDays,
        labelHours,
        labelMinutes,
        labelSeconds,
        expiredMessage,
    } = attributes;

    const [remaining, setRemaining] = useState(() => getTimeRemaining(targetDate));

    useEffect(() => {
        const timer = setInterval(() => {
            setRemaining(getTimeRemaining(targetDate));
        }, 1000);

        return () => clearInterval(timer);
    }, [targetDate]);

    const blockProps = useBlockProps();

    const isExpired = targetDate && !remaining;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Countdown Settings', 'wp-coming-soon-by-mudrava')}>
                    <div style={{ marginBottom: '16px' }}>
                        <label
                            htmlFor="mcs-countdown-target"
                            style={{ display: 'block', marginBottom: '8px', fontWeight: 600 }}
                        >
                            {__('Target Date', 'wp-coming-soon-by-mudrava')}
                        </label>
                        <input
                            id="mcs-countdown-target"
                            type="datetime-local"
                            value={targetDate ? targetDate.slice(0, 16) : ''}
                            onChange={(e) => {
                                const val = e.target.value;
                                setAttributes({ targetDate: val ? new Date(val).toISOString() : '' });
                            }}
                            style={{ width: '100%', maxWidth: '280px' }}
                        />
                    </div>
                </PanelBody>
                <PanelBody title={__('Visible Units', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                    <ToggleControl
                        label={__('Show Days', 'wp-coming-soon-by-mudrava')}
                        checked={showDays}
                        onChange={(val) => setAttributes({ showDays: val })}
                    />
                    <ToggleControl
                        label={__('Show Hours', 'wp-coming-soon-by-mudrava')}
                        checked={showHours}
                        onChange={(val) => setAttributes({ showHours: val })}
                    />
                    <ToggleControl
                        label={__('Show Minutes', 'wp-coming-soon-by-mudrava')}
                        checked={showMinutes}
                        onChange={(val) => setAttributes({ showMinutes: val })}
                    />
                    <ToggleControl
                        label={__('Show Seconds', 'wp-coming-soon-by-mudrava')}
                        checked={showSeconds}
                        onChange={(val) => setAttributes({ showSeconds: val })}
                    />
                </PanelBody>
                <PanelBody title={__('Labels', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                    <TextControl
                        label={__('Days Label', 'wp-coming-soon-by-mudrava')}
                        value={labelDays}
                        onChange={(val) => setAttributes({ labelDays: val })}
                    />
                    <TextControl
                        label={__('Hours Label', 'wp-coming-soon-by-mudrava')}
                        value={labelHours}
                        onChange={(val) => setAttributes({ labelHours: val })}
                    />
                    <TextControl
                        label={__('Minutes Label', 'wp-coming-soon-by-mudrava')}
                        value={labelMinutes}
                        onChange={(val) => setAttributes({ labelMinutes: val })}
                    />
                    <TextControl
                        label={__('Seconds Label', 'wp-coming-soon-by-mudrava')}
                        value={labelSeconds}
                        onChange={(val) => setAttributes({ labelSeconds: val })}
                    />
                </PanelBody>
                <PanelBody title={__('Expired State', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                    <TextControl
                        label={__('Expired Message', 'wp-coming-soon-by-mudrava')}
                        value={expiredMessage}
                        onChange={(val) => setAttributes({ expiredMessage: val })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!targetDate && (
                    <p className="wp-block-mudrava-countdown__placeholder">
                        {__('Set a target date in the block settings →', 'wp-coming-soon-by-mudrava')}
                    </p>
                )}

                {isExpired && (
                    <p className="wp-block-mudrava-countdown__expired">
                        {expiredMessage}
                    </p>
                )}

                {remaining && (
                    <div className="wp-block-mudrava-countdown__grid">
                        {showDays && (
                            <div className="wp-block-mudrava-countdown__unit">
                                <span className="wp-block-mudrava-countdown__number">
                                    {padZero(remaining.days)}
                                </span>
                                <span className="wp-block-mudrava-countdown__label">
                                    {labelDays}
                                </span>
                            </div>
                        )}
                        {showHours && (
                            <div className="wp-block-mudrava-countdown__unit">
                                <span className="wp-block-mudrava-countdown__number">
                                    {padZero(remaining.hours)}
                                </span>
                                <span className="wp-block-mudrava-countdown__label">
                                    {labelHours}
                                </span>
                            </div>
                        )}
                        {showMinutes && (
                            <div className="wp-block-mudrava-countdown__unit">
                                <span className="wp-block-mudrava-countdown__number">
                                    {padZero(remaining.minutes)}
                                </span>
                                <span className="wp-block-mudrava-countdown__label">
                                    {labelMinutes}
                                </span>
                            </div>
                        )}
                        {showSeconds && (
                            <div className="wp-block-mudrava-countdown__unit">
                                <span className="wp-block-mudrava-countdown__number">
                                    {padZero(remaining.seconds)}
                                </span>
                                <span className="wp-block-mudrava-countdown__label">
                                    {labelSeconds}
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
