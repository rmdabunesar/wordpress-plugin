import { TextControl, ToggleControl } from "@wordpress/components";

const PaymentSettings = ({ settings, update }) => {
    return (
        <div style={{ maxWidth: "300px" }}>
        <TextControl
                label="AamarPay Store ID"
                value={settings.store_id || ''}
                onChange={(value) => update('store_id', value)}
            />

        <TextControl
                label="AamarPay Signature Key"
                value={settings.signature_key || ''}
                onChange={(value) => update('signature_key', value)}
            />

        <ToggleControl
                label="Use Sandbox Mode (Testing)"
                checked={settings.sandbox_mode === '1'}
                onChange={(value) => update('sandbox_mode', value ? '1' : '0')}
            />
        </div>
    );
};

export default PaymentSettings;
