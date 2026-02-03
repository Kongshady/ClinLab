import AppLayout from '../../Layouts/AppLayout';
import { Link } from '@inertiajs/react';

export default function Transactions({ transactions }) {
    return (
        <AppLayout title="Transactions">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div className="flex justify-between mb-6">
                    <h1 className="text-2xl font-semibold">Transactions</h1>
                    <Link href="/transactions/create" className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Add New</Link>
                </div>
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OR Number</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {transactions.data.map(t => (
                            <tr key={t.transaction_id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 text-sm">{t.transaction_id}</td>
                                <td className="px-6 py-4 text-sm">{t.or_number}</td>
                                <td className="px-6 py-4 text-sm">{t.patient?.firstname} {t.patient?.lastname}</td>
                                <td className="px-6 py-4 text-sm">{new Date(t.datetime_added).toLocaleDateString()}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
