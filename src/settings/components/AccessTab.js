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
import { useState, useMemo, useCallback, useRef } from '@wordpress/element';
import { FormTokenField } from '@wordpress/components';

export default function AccessTab({ settings, updateSetting }) {
    const { roles = {} } = window.mudravaComingSoon || {};
    const { previewUrl = '' } = window.mudravaComingSoon || {};

    const [copied, setCopied] = useState(false);
    const inputRef = useRef(null);

    const bypassRoles = settings.bypass_roles || [];

    /**
     * Build the full preview URL dynamically so it always reflects the
     * current token value (including after regeneration).
     */
    const currentPreviewUrl = useMemo(() => {
        const token = settings.preview_token || '';
        if (!token) return '';
        try {
            const url = new URL(previewUrl || window.location.origin);
            url.searchParams.set('mudrava_preview', token);
            return url.toString();
        } catch {
            return previewUrl;
        }
    }, [settings.preview_token, previewUrl]);

    const handleRoleToggle = (role, checked) => {
        const updated = checked
            ? [...bypassRoles, role]
            : bypassRoles.filter((r) => r !== role);
        updateSetting('bypass_roles', updated);
    };

    const handleCopyPreview = useCallback(() => {
        if (!currentPreviewUrl) return;

        /* Try modern Clipboard API first, fall back to execCommand. */
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(currentPreviewUrl).then(() => {
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            }).catch(() => {
                fallbackCopy(currentPreviewUrl);
            });
        } else {
            fallbackCopy(currentPreviewUrl);
        }
    }, [currentPreviewUrl]);

    const fallbackCopy = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            /* Silent fail — unlikely in admin context. */
        }
        document.body.removeChild(textarea);
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
                    {__('Share this link to let someone preview the site without logging in.', 'wp-coming-soon-by-mudrava')}
                </p>
                <TextControl
                    ref={inputRef}
                    value={currentPreviewUrl}
                    readOnly
                    label={__('Preview URL', 'wp-coming-soon-by-mudrava')}
                    onFocus={(e) => e.target.select()}
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
