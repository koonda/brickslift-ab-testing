/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element'; // Added useState
import { Button } from '@wordpress/components'; // Added Button

/**
 * Internal dependencies
 */
import Dashboard from './components/Dashboard/Dashboard';
import TestEditor from './components/TestEditor/TestEditor'; // Added TestEditor
// import './App.scss'; // If you need specific App styles

function App() {
	const [currentView, setCurrentView] = useState('dashboard'); // 'dashboard' or 'editor'
	const [editingTestId, setEditingTestId] = useState(null); // ID of the test to edit, or null for new

	const handleNavigateToEditor = (testId = null) => {
		setEditingTestId(testId);
		setCurrentView('editor');
	};

	const handleNavigateToDashboard = () => {
		setCurrentView('dashboard');
		setEditingTestId(null); // Clear editing ID when going back to dashboard
	};

	let viewComponent;
	if (currentView === 'editor') {
		viewComponent = <TestEditor testId={editingTestId} onSave={handleNavigateToDashboard} onCancel={handleNavigateToDashboard} />;
	} else {
		// Pass a function to Dashboard to allow navigation to editor
		// This is a simplified way; proper routing would handle this better.
		viewComponent = (
			<div>
				<div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
					<h1>{__('BricksLift A/B Testing', 'brickslift-ab-testing')}</h1>
					<Button variant="primary" onClick={() => handleNavigateToEditor()}>
						{__('Create New Test', 'brickslift-ab-testing')}
					</Button>
				</div>
				<Dashboard onEditTest={handleNavigateToEditor} />
			</div>
		);
	}

	return (
		<Fragment>
			{/* Basic navigation for demo purposes */}
			{currentView === 'editor' && (
				<Button isLink onClick={handleNavigateToDashboard} style={{ marginBottom: '1em' }}>
					&larr; {__('Back to Dashboard', 'brickslift-ab-testing')}
				</Button>
			)}
			{viewComponent}
		</Fragment>
	);
}

export default App;