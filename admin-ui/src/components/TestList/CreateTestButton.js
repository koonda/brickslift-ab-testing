import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const CreateTestButton = ({ onClick }) => {
    const handleClick = () => {
        console.log('Create New Test button clicked (from CreateTestButton component)');
        if (onClick) {
            onClick();
        }
    };

    return (
        <Button
            variant="primary"
            onClick={handleClick}
            style={{ marginBottom: '20px' }}
        >
            {__('Create New Test', 'brickslift-ab-testing')}
        </Button>
    );
};

export default CreateTestButton;