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
import TestListContainer from './components/TestList/TestListContainer';
import TestDetailContainer from './components/TestDetail/TestDetailContainer'; // Added TestDetailContainer
// import './App.scss'; // If you need specific App styles

function App() {
	const [currentView, setCurrentView] = useState('dashboard'); // 'dashboard', 'editor', 'statsDashboard', 'testList', or 'testDetail'
	const [editingTestId, setEditingTestId] = useState(null); // ID of the test to edit
	const [viewingStatsForTestId, setViewingStatsForTestId] = useState(null); // ID of the test for stats view
	const [viewingTestDetailId, setViewingTestDetailId] = useState(null); // ID of the test for detail view

	const handleNavigateToEditor = (testId = null) => {
		setEditingTestId(testId);
		setViewingStatsForTestId(null);
		setViewingTestDetailId(null);
		setCurrentView('editor');
	};

	const handleNavigateToStatsDashboard = (testId) => {
		setViewingStatsForTestId(testId);
		setEditingTestId(null);
		setViewingTestDetailId(null);
		setCurrentView('statsDashboard');
	};

	const handleNavigateToTestDetail = (testId) => {
		setViewingTestDetailId(testId);
		setEditingTestId(null);
		setViewingStatsForTestId(null);
		setCurrentView('testDetail');
	};

	const handleNavigateToDashboard = () => {
		setCurrentView('dashboard');
		setEditingTestId(null);
		setViewingStatsForTestId(null);
		setViewingTestDetailId(null);
	};

	const handleNavigateToTestList = () => {
		setCurrentView('testList');
		setEditingTestId(null);
		setViewingStatsForTestId(null);
		setViewingTestDetailId(null);
	};

	// Function to be passed to TestEditor for navigation after save/update
	const handleSaveTest = (savedTestId) => {
		// Navigate to the detail page of the saved test
		handleNavigateToTestDetail(savedTestId);
	};

	let viewComponent;
	let pageTitle = __('BricksLift A/B Testing', 'brickslift-ab-testing');
	let showBackToDashboardButton = false;
	let showBackToTestListButton = false;

	if (currentView === 'editor') {
		pageTitle = editingTestId ? __('Edit Test', 'brickslift-ab-testing') : __('Create New Test', 'brickslift-ab-testing');
		// Pass handleSaveTest to navigate to detail view on save
		viewComponent = <TestEditor testId={editingTestId} onSaveSuccess={handleSaveTest} onCancel={handleNavigateToTestList} />;
		showBackToTestListButton = true;
	} else if (currentView === 'statsDashboard') {
		// This view might be deprecated or merged into TestDetail. For now, keep it.
		pageTitle = __('Test Statistics', 'brickslift-ab-testing');
		viewComponent = <TestStatsDashboard testId={viewingStatsForTestId} />;
		showBackToTestListButton = true; // Or back to detail if coming from there
	} else if (currentView === 'testList') {
		pageTitle = __('All A/B Tests', 'brickslift-ab-testing');
		viewComponent = <TestListContainer onNavigateToEditor={handleNavigateToEditor} onNavigateToDetail={handleNavigateToTestDetail} />;
		showBackToDashboardButton = true;
	} else if (currentView === 'testDetail') {
		pageTitle = __('Test Details', 'brickslift-ab-testing');
		// TestDetailContainer will fetch its own data using viewingTestDetailId from useParams,
		// but we pass it here to ensure the view is triggered correctly.
		// The actual TestDetailContainer will use `useParams` if we switch to react-router later.
		// For now, we pass it as a prop to simplify.
		viewComponent = <TestDetailContainer testId={viewingTestDetailId} onNavigateToEdit={handleNavigateToEditor} />;
		showBackToTestListButton = true;
	}
	 else { // Dashboard view
		viewComponent = (
			<Dashboard onEditTest={handleNavigateToEditor} onViewStats={handleNavigateToStatsDashboard} onViewAllTests={handleNavigateToTestList} />
		);
	}

	return (
		<Fragment>
			<div style={{ marginBottom: '20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
				<h1>{pageTitle}</h1>
				{currentView === 'dashboard' && (
					<div>
						<Button variant="secondary" onClick={handleNavigateToTestList} style={{ marginRight: '10px' }}>
							{__('View All Tests', 'brickslift-ab-testing')}
						</Button>
						<Button variant="primary" onClick={() => handleNavigateToEditor()}>
							{__('Create New Test', 'brickslift-ab-testing')}
						</Button>
					</div>
				)}
			</div>

			{showBackToDashboardButton && (
				<Button isLink onClick={handleNavigateToDashboard} style={{ marginBottom: '1em', display: 'block' }}>
					&larr; {__('Back to Dashboard', 'brickslift-ab-testing')}
				</Button>
			)}
			{showBackToTestListButton && (
				<Button isLink onClick={handleNavigateToTestList} style={{ marginBottom: '1em', display: 'block' }}>
					&larr; {__('Back to Test List', 'brickslift-ab-testing')}
				</Button>
			)}
			{viewComponent}
		</Fragment>
	);
}

export default App;