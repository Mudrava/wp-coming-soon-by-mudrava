/**
 * Settings – Access Tab
 *
 * Controls for bypass roles, IP whitelist, and preview link management.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    PanelRow,
    CheckboxControl,
    TextControl,
    Button,
    ExternalLink,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { FormTokenField } from '@wordpress/components';

export default function AccessTab({ settings, updateSetting }) {
    const { roles = {} } = window.mudravaComingSoon || {};
    const { previewUrl = '' } = window.mudravaComingSoon || {};

    const [copied, setCopied] = useState(false);

    const bypassRoles = settings.bypass_roles || [];

    const handleRoleToggle = (role, checked) => {
        const updated = checked
            ? [...bypassRoles, role]
            : bypassRoles.filter((r) => r !== role);
        updateSetting('bypass_roles', updated);
    };

    const handleCopyPreview = () => {
        navigator.clipboard.writeText(previewUrl).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    const handleRegenerateToken = () => {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let token = '';
        for (let i = 0; i < 32; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        updateSetting('preview_token', token);
    };

    return (
        <div className="mudrava-cs-settings__tab-content">
            <PanelBody title={__('Bypass Roles', 'wp-coming-soon-by-mudrava')}>
                <p className="components-base-control__help" style={{ marginTop: 0 }}>
                    {__('Logged-in users with these roles can bypass the Coming Soon page.', 'wp-coming-soon-by-mudrava')}
                </p>
                {Object.entries(roles).map(([slug, name]) => (
                    <CheckboxControl
                        key={slug}
                        label={name}
                        checked={bypassRoles.includes(slug)}
                        onChange={(checked) => handleRoleToggle(slug, checked)}
                        disabled={slug === 'administrator'}
                    />
                ))}
            </PanelBody>

            <PanelBody title={__('IP Whitelist', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <p className="components-base-control__help" style={{ marginTop: 0 }}>
                    {__('Add IP addresses or CIDR ranges that can bypass the Coming Soon page.', 'wp-coming-soon-by-mudrava')}
                </p>
                <FormTokenField
                    value={settings.ip_whitelist || []}
                    onChange={(tokens) => updateSetting('ip_whitelist', tokens)}
                    placeholder={__('Enter IP address…', 'wp-coming-soon-by-mudrava')}
                    tokenizeOnSpace
                />
            </PanelBody>

            <PanelBody title={__('Preview Link', 'wp-coming-soon-by-mudrava')} initialOpen={false}>
                <p className="components-base-control__help" style={{ marginTop: 0 }}>
                    {__('Share this link to let someone preview the Coming Soon page without logging in.', 'wp-coming-soon-by-mudrava')}
                </p>
                <TextControl
                    value={settings.preview_token || ''}
                    readOnly
                    label={__('Preview Token', 'wp-coming-soon-by-mudrava')}
                />
                <div style={{ display: 'flex', gap: '8px', marginTop: '8px' }}>
                    <Button
                        variant="secondary"
                        onClick={handleCopyPreview}
                    >
                        {copied
                            ? __('Copied!', 'wp-coming-soon-by-mudrava')
                            : __('Copy Preview Link', 'wp-coming-soon-by-mudrava')}
                    </Button>
                    <Button
                        variant="tertiary"
                        onClick={handleRegenerateToken}
                    >
                        {__('Regenerate', 'wp-coming-soon-by-mudrava')}
                    </Button>
                </div>
            </PanelBody>
        </div>
    );
}
