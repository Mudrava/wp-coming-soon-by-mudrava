/**
 * Settings – Advanced Tab
 *
 * Controls for background appearance (color, image, size, position,
 * blur, overlay), social links with custom labels, custom CSS,
 * and admin bar toggle.
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
    RangeControl,
    SelectControl,
    Button,
    __experimentalHStack as HStack,
} from '@wordpress/components';
import { useCallback } from '@wordpress/element';

/**
 * Compact color input: native color swatch + hex text field in one row.
 */
function ColorInput({ label, value, onChange }) {
    const hex = value || '#000000';
    return (
        <div className="mudrava-cs-settings__color-input">
            {label && (
                <label className="components-base-control__label">{label}</label>
            )}
            <HStack spacing={3}>
                <input
                    type="color"
                    value={hex.startsWith('#') ? hex : `#${hex}`}
                    onChange={(e) => onChange(e.target.value)}
                    className="mudrava-cs-settings__color-swatch"
                />
                <TextControl
                    value={hex}
                    onChange={(val) => {
                        const clean = val.startsWith('#') ? val : `#${val}`;
                        if (/^#[0-9a-fA-F]{0,6}$/.test(clean)) {
                            onChange(clean);
                        }
                    }}
                    __nextHasNoMarginBottom
                />
            </HStack>
        </div>
    );
}

const PLATFORM_OPTIONS = [
    { label: 'Facebook', value: 'facebook' },
    { label: 'X (Twitter)', value: 'twitter' },
    { label: 'Instagram', value: 'instagram' },
    { label: 'LinkedIn', value: 'linkedin' },
    { label: 'YouTube', value: 'youtube' },
    { label: 'TikTok', value: 'tiktok' },
    { label: 'Pinterest', value: 'pinterest' },
    { label: 'GitHub', value: 'github' },
    { label: 'Telegram', value: 'telegram' },
    { label: 'Custom', value: 'custom' },
];

const BACKGROUND_SIZE_OPTIONS = [
    { label: __('Cover', 'wp-coming-soon-by-mudrava'), value: 'cover' },
    { label: __('Contain', 'wp-coming-soon-by-mudrava'), value: 'contain' },
    { label: __('Auto', 'wp-coming-soon-by-mudrava'), value: 'auto' },
];

const BACKGROUND_POSITION_OPTIONS = [
    { label: __('Center Center', 'wp-coming-soon-by-mudrava'), value: 'center center' },
    { label: __('Top Left', 'wp-coming-soon-by-mudrava'), value: 'top left' },
    { label: __('Top Center', 'wp-coming-soon-by-mudrava'), value: 'top center' },
    { label: __('Top Right', 'wp-coming-soon-by-mudrava'), value: 'top right' },
    { label: __('Center Left', 'wp-coming-soon-by-mudrava'), value: 'center left' },
    { label: __('Center Right', 'wp-coming-soon-by-mudrava'), value: 'center right' },
    { label: __('Bottom Left', 'wp-coming-soon-by-mudrava'), value: 'bottom left' },
    { label: __('Bottom Center', 'wp-coming-soon-by-mudrava'), value: 'bottom center' },
    { label: __('Bottom Right', 'wp-coming-soon-by-mudrava'), value: 'bottom right' },
];

export default function AdvancedTab({ settings, updateSetting }) {
    const socialLinks = settings.social_links || [];

    /**
     * Open the native WordPress media frame to select a background image.
     * This avoids the MediaUpload SlotFill which only works inside the block editor.
     */
    const openMediaFrame = useCallback(() => {
        const frame = wp.media({
            title: __('Select Background Image', 'wp-coming-soon-by-mudrava'),
            button: { text: __('Select', 'wp-coming-soon-by-mudrava') },
            multiple: false,
            library: { type: 'image' },
        });
        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            updateSetting('background_image', attachment.url);
        });
        frame.open();
    }, [updateSetting]);

    const updateSocialLink = (index, key, value) => {
        const updated = [...socialLinks];
        updated[index] = { ...updated[index], [key]: value };
        updateSetting('social_links', updated);
    };

    const addSocialLink = () => {
        updateSetting('social_links', [
            ...socialLinks,
            { platform: 'facebook', url: '', label: '' },
        ]);
    };

    const removeSocialLink = (index) => {
        const updated = socialLinks.filter((_, i) => i !== index);
        updateSetting('social_links', updated);
    };

    const hasBackgroundImage = !!settings.background_image;

    return (
        <div className="mudrava-cs-settings__tab-content">
            <PanelBody title={__('Background', 'wp-coming-soon-by-mudrava')}>
                <ColorInput
                    label={__('Background Color', 'wp-coming-soon-by-mudrava')}
                    value={settings.background_color || '#0f172a'}
                    onChange={(color) => updateSetting('background_color', color)}
                />

                <div style={{ marginBottom: '16px' }}>
                    <label style={{ display: 'block', marginBottom: '8px', fontWeight: 600 }}>
                        {__('Background Image', 'wp-coming-soon-by-mudrava')}
                    </label>
                    {settings.background_image ? (
                        <div style={{ marginBottom: '8px' }}>
                            <img
                                src={settings.background_image}
                                alt=""
                                style={{
                                    maxWidth: '100%',
                                    maxHeight: '200px',
                                    objectFit: 'cover',
                                    borderRadius: '4px',
                                }}
                            />
                        </div>
                    ) : null}
                    <div style={{ display: 'flex', gap: '8px' }}>
                        <Button variant="secondary" onClick={openMediaFrame}>
                            {settings.background_image
                                ? __('Replace Image', 'wp-coming-soon-by-mudrava')
                                : __('Select Image', 'wp-coming-soon-by-mudrava')}
                        </Button>
                        {settings.background_image && (
                            <Button
                                variant="tertiary"
                                isDestructive
                                onClick={() => updateSetting('background_image', '')}
                            >
                                {__('Remove', 'wp-coming-soon-by-mudrava')}
                            </Button>
                        )}
                    </div>
                </div>

                {hasBackgroundImage && (
                    <div className="mudrava-cs-settings__bg-options">
                        <SelectControl
                            label={__('Background Size', 'wp-coming-soon-by-mudrava')}
                            value={settings.background_size || 'cover'}
                            options={BACKGROUND_SIZE_OPTIONS}
                            onChange={(val) => updateSetting('background_size', val)}
                        />
                        <SelectControl
                            label={__('Background Position', 'wp-coming-soon-by-mudrava')}
                            value={settings.background_position || 'center center'}
                            options={BACKGROUND_POSITION_OPTIONS}
                            onChange={(val) => updateSetting('background_position', val)}
                        />
                        <RangeControl
                            label={__('Blur (px)', 'wp-coming-soon-by-mudrava')}
                            value={settings.background_blur || 0}
                            onChange={(val) => updateSetting('background_blur', val)}
                            min={0}
                            max={20}
                        />
                        <ColorInput
                            label={__('Overlay Color', 'wp-coming-soon-by-mudrava')}
                            value={settings.background_overlay_color || '#000000'}
                            onChange={(color) => updateSetting('background_overlay_color', color)}
                        />
                        <RangeControl
                            label={__('Overlay Opacity (%)', 'wp-coming-soon-by-mudrava')}
                            value={settings.background_overlay_opacity || 0}
                            onChange={(val) => updateSetting('background_overlay_opacity', val)}
                            min={0}
                            max={100}
                        />
                    </div>
                )}
            </PanelBody>

            <PanelBody title={__('Social Links', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                {socialLinks.map((link, index) => (
                    <div className="mudrava-cs-settings__social-row" key={index}>
                        <SelectControl
                            value={link.platform || 'facebook'}
                            options={PLATFORM_OPTIONS}
                            onChange={(val) => updateSocialLink(index, 'platform', val)}
                        />
                        <TextControl
                            placeholder={__('URL', 'wp-coming-soon-by-mudrava')}
                            value={link.url || ''}
                            onChange={(val) => updateSocialLink(index, 'url', val)}
                        />
                        <TextControl
                            placeholder={link.platform ? link.platform.charAt(0).toUpperCase() + link.platform.slice(1) : __('Label', 'wp-coming-soon-by-mudrava')}
                            value={link.label || ''}
                            onChange={(val) => updateSocialLink(index, 'label', val)}
                        />
                        <Button
                            icon="trash"
                            isDestructive
                            onClick={() => removeSocialLink(index)}
                            label={__('Remove', 'wp-coming-soon-by-mudrava')}
                        />
                    </div>
                ))}
                <Button variant="secondary" onClick={addSocialLink}>
                    {__('Add Social Link', 'wp-coming-soon-by-mudrava')}
                </Button>
            </PanelBody>

            <PanelBody title={__('Custom CSS', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <TextareaControl
                    label={__('Custom CSS', 'wp-coming-soon-by-mudrava')}
                    help={__('Add custom CSS rules for the Coming Soon page.', 'wp-coming-soon-by-mudrava')}
                    value={settings.custom_css || ''}
                    onChange={(val) => updateSetting('custom_css', val)}
                    rows={10}
                    className="mudrava-cs-settings__code-editor"
                />
            </PanelBody>

            <PanelBody title={__('Admin Bar', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <PanelRow>
                    <ToggleControl
                        label={__('Show Admin Bar Indicator', 'wp-coming-soon-by-mudrava')}
                        help={__('Display a "Coming Soon: ON" indicator in the WordPress admin bar.', 'wp-coming-soon-by-mudrava')}
                        checked={settings.show_admin_bar !== false}
                        onChange={(val) => updateSetting('show_admin_bar', val)}
                    />
                </PanelRow>
            </PanelBody>
        </div>
    );
}
