/**
 * Countdown Block Registration
 *
 * Registers the mudrava/countdown block using block.json metadata.
 * The block is dynamic (server-side rendered), so save() returns null.
 * A deprecated entry handles migration from the earlier static save.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import edit from './edit';
import save from './save';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

/**
 * Deprecated v1: static HTML save (before dynamic block conversion).
 */
const deprecatedV1Save = ({ attributes }) => {
    const {
        targetDate, showDays, showHours, showMinutes, showSeconds,
        labelDays, labelHours, labelMinutes, labelSeconds, expiredMessage,
    } = attributes;

    if (!targetDate) return null;

    const labels = {
        days: labelDays, hours: labelHours,
        minutes: labelMinutes, seconds: labelSeconds,
    };
    const blockProps = useBlockProps.save();

    return (
        <div
            {...blockProps}
            data-target-date={targetDate}
            data-labels={JSON.stringify(labels)}
            data-expired-message={expiredMessage}
        >
            <div className="wp-block-mudrava-countdown__grid">
                {showDays && (
                    <div className="wp-block-mudrava-countdown__unit" data-unit="days">
                        <span className="wp-block-mudrava-countdown__number">00</span>
                        <span className="wp-block-mudrava-countdown__label">{labelDays}</span>
                    </div>
                )}
                {showHours && (
                    <div className="wp-block-mudrava-countdown__unit" data-unit="hours">
                        <span className="wp-block-mudrava-countdown__number">00</span>
                        <span className="wp-block-mudrava-countdown__label">{labelHours}</span>
                    </div>
                )}
                {showMinutes && (
                    <div className="wp-block-mudrava-countdown__unit" data-unit="minutes">
                        <span className="wp-block-mudrava-countdown__number">00</span>
                        <span className="wp-block-mudrava-countdown__label">{labelMinutes}</span>
                    </div>
                )}
                {showSeconds && (
                    <div className="wp-block-mudrava-countdown__unit" data-unit="seconds">
                        <span className="wp-block-mudrava-countdown__number">00</span>
                        <span className="wp-block-mudrava-countdown__label">{labelSeconds}</span>
                    </div>
                )}
            </div>
        </div>
    );
};

registerBlockType(metadata.name, {
    edit,
    save,
    deprecated: [
        {
            attributes: metadata.attributes,
            save: deprecatedV1Save,
        },
    ],
});
