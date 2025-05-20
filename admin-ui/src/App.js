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
import TestStatsDashboard from './components/TestStatsDashboard/TestStatsDashboard'; // Will be created
// import './App.scss'; // If you need specific App styles

function App() {
	const [currentView, setCurrentView] = useState('dashboard'); // 'dashboard', 'editor', or 'statsDashboard'
	const [editingTestId, setEditingTestId] = useState(null); // ID of the test to edit
	const [viewingStatsForTestId, setViewingStatsForTestId] = useState(null); // ID of the test for stats view

	const handleNavigateToEditor = (testId = null) => {
		setEditingTestId(testId);
		setViewingStatsForTestId(null);
		setCurrentView('editor');
	};

	const handleNavigateToStatsDashboard = (testId) => {
		setViewingStatsForTestId(testId);
		setEditingTestId(null);
		setCurrentView('statsDashboard');
	};

	const handleNavigateToDashboard = () => {
		setCurrentView('dashboard');
		setEditingTestId(null);
		setViewingStatsForTestId(null);
	};

	let viewComponent;
	if (currentView === 'editor') {
		viewComponent = <TestEditor testId={editingTestId} onSave={handleNavigateToDashboard} onCancel={handleNavigateToDashboard} />;
	} else if (currentView === 'statsDashboard') {
		viewComponent = <TestStatsDashboard testId={viewingStatsForTestId} />;
	} else {
		// Pass functions to Dashboard to allow navigation
		viewComponent = (
			<div>
				<div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
					<h1>{__('BricksLift A/B Testing', 'brickslift-ab-testing')}</h1>
					<Button variant="primary" onClick={() => handleNavigateToEditor()}>
						{__('Create New Test', 'brickslift-ab-testing')}
					</Button>
				</div>
				<Dashboard onEditTest={handleNavigateToEditor} onViewStats={handleNavigateToStatsDashboard} />
			</div>
		);
	}

	return (
		<Fragment>
			{/* Basic navigation for demo purposes */}
			{(currentView === 'editor' || currentView === 'statsDashboard') && (
				<Button isLink onClick={handleNavigateToDashboard} style={{ marginBottom: '1em' }}>
					&larr; {__('Back to Dashboard', 'brickslift-ab-testing')}
				</Button>
			)}
			{viewComponent}
		</Fragment>
	);
}

export default App;