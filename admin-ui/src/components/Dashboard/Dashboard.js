/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components'; // Import Button

/**
 * Internal dependencies
 */
import './Dashboard.scss'; // We'll create this for styling

const Dashboard = ({ onEditTest, onViewStats }) => { // Added onViewStats prop
	const [tests, setTests] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [error, setError] = useState(null);

	useEffect(() => {
		setIsLoading(true);
		setError(null);

		apiFetch({ path: '/blft/v1/tests' })
			.then((fetchedTests) => {
				setTests(fetchedTests);
				setIsLoading(false);
			})
			.catch((fetchError) => {
				setError(fetchError.message || __('Failed to load tests.', 'brickslift-ab-testing'));
				setIsLoading(false);
				// eslint-disable-next-line no-console
				console.error('Error fetching tests:', fetchError);
			});
	}, []);

	if (isLoading) {
		return <p>{__('Loading tests...', 'brickslift-ab-testing')}</p>;
	}

	if (error) {
		return <p style={{ color: 'red' }}>{__('Error:', 'brickslift-ab-testing')} {error}</p>;
	}

	if (tests.length === 0) {
		return <p>{__('No A/B tests found. Create your first test!', 'brickslift-ab-testing')}</p>;
	}

	return (
		<div className="blft-dashboard">
			<h2>{__('A/B Tests Dashboard', 'brickslift-ab-testing')}</h2>
			<table className="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col">{__('ID', 'brickslift-ab-testing')}</th>
						<th scope="col">{__('Title', 'brickslift-ab-testing')}</th>
						<th scope="col">{__('Status (A/B)', 'brickslift-ab-testing')}</th>
						<th scope="col">{__('Date Created', 'brickslift-ab-testing')}</th>
						<th scope="col">{__('Actions', 'brickslift-ab-testing')}</th>
					</tr>
				</thead>
				<tbody>
					{tests.map((test) => (
						<tr key={test.id}>
							<td>{test.id}</td>
							<td>
								<Button isLink onClick={() => onEditTest ? onEditTest(test.id) : null}>
									{test.title?.rendered || test.title?.raw || __('N/A', 'brickslift-ab-testing')}
								</Button>
							</td>
							<td>{test.blft_status || __('N/A', 'brickslift-ab-testing')}</td>
							<td>{test.date_created ? new Date(test.date_created).toLocaleDateString() : __('N/A', 'brickslift-ab-testing')}</td>
							<td>
								<Button
									variant="secondary"
									onClick={() => onEditTest ? onEditTest(test.id) : null}
									style={{ marginRight: '8px' }}
								>
									{__('Edit', 'brickslift-ab-testing')}
								</Button>
								<Button
									variant="secondary"
									onClick={() => onViewStats ? onViewStats(test.id) : null}
								>
									{__('View Stats', 'brickslift-ab-testing')}
								</Button>
								{/* Add other actions like "Pause", "Delete" later */}
							</td>
						</tr>
					))}
				</tbody>
			</table>
		</div>
	);
};

export default Dashboard;