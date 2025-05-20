/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody, PanelRow, TextControl, Button } from '@wordpress/components';

const VariationManager = ({ variants, addVariant, updateVariant, removeVariant, validationErrors }) => {
    const getVariantFieldError = (index, fieldName) => {
        // Assuming validationErrors.variantNames is an array of error messages for names
        // and validationErrors.variants is a general message for distribution sum.
        if (fieldName === 'name' && validationErrors && validationErrors.variantNames && validationErrors.variantNames[index]) {
            return validationErrors.variantNames[index];
        }
        return null; // No specific error for this field/index
    };

    return (
        <PanelBody title={__('Variations', 'brickslift-ab-testing')} initialOpen={true}>
            {validationErrors && validationErrors.variants && (
                <PanelRow>
                    <p style={{ color: 'red' }}>{validationErrors.variants}</p>
                </PanelRow>
            )}
            {variants.map((variant, index) => (
                <PanelRow key={variant.id || index} className={`blft-variant-row ${getVariantFieldError(index, 'name') ? 'is-invalid' : ''}`}>
                    <TextControl
                        label={__('Variant Name', 'brickslift-ab-testing')}
                        value={variant.name}
                        onChange={(val) => updateVariant(index, 'name', val)}
                        className="blft-variant-name"
                        help={getVariantFieldError(index, 'name')}
                    />
                    <TextControl
                        label={__('Distribution (%)', 'brickslift-ab-testing')}
                        type="number"
                        min="0"
                        max="100"
                        value={variant.distribution}
                        onChange={(val) => updateVariant(index, 'distribution', val)}
                        className="blft-variant-distribution"
                    />
                    <TextControl
                        label={__('Content URL/Selector (Optional)', 'brickslift-ab-testing')}
                        value={variant.content_url || ''}
                        onChange={(val) => updateVariant(index, 'content_url', val)}
                        className="blft-variant-content-url"
                        help={__('e.g., /path-to-variant-page or #element-id. Leave empty to use original content.', 'brickslift-ab-testing')}
                    />
                    {variants.length > 1 && ( // Only show remove if more than one variant
                        <Button isLink isDestructive onClick={() => removeVariant(index)}>
                            {__('Remove', 'brickslift-ab-testing')}
                        </Button>
                    )}
                </PanelRow>
            ))}
            <Button variant="secondary" onClick={addVariant}>
                {__('Add Variant', 'brickslift-ab-testing')}
            </Button>
        </PanelBody>
    );
};

export default VariationManager;