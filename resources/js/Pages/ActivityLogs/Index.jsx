import AppLayout from '../../Layouts/AppLayout';

export default function ActivityLogs({ logs }) {
    return (
        <AppLayout title="Activity Logs">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <h1 className="text-2xl font-semibold mb-6">Activity Logs</h1>
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {logs.data.map(log => (
                            <tr key={log.activity_log_id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 text-sm">{log.activity_log_id}</td>
                                <td className="px-6 py-4 text-sm">{log.employee?.full_name || 'N/A'}</td>
                                <td className="px-6 py-4 text-sm">{log.description}</td>
                                <td className="px-6 py-4 text-sm">{new Date(log.datetime_added).toLocaleString()}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
