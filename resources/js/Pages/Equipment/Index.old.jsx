import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Equipment({ equipment, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const handleDelete = (id) => { if (confirm('Are you sure?')) { router.delete(`/equipment/${id}`, { onSuccess: () => setFlashMessage('Equipment deleted successfully.') }); } };

    return (
        <AppLayout title="Equipment">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <div className="flex justify-between mb-6">
                    <h1 className="text-2xl font-semibold">Equipment</h1>
                    <Link href="/equipment/create" className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Add New</Link>
                </div>
                {flashMessage && <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{flashMessage}</div>}
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial No</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {equipment.data.map(eq => (
                            <tr key={eq.equipment_id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 text-sm">{eq.equipment_id}</td>
                                <td className="px-6 py-4 text-sm font-medium">{eq.name}</td>
                                <td className="px-6 py-4 text-sm">{eq.model || 'N/A'}</td>
                                <td className="px-6 py-4 text-sm">{eq.serial_no || 'N/A'}</td>
                                <td className="px-6 py-4 text-sm"><span className={`px-2 py-1 rounded text-xs ${eq.status === 'operational' ? 'bg-green-100 text-green-800' : eq.status === 'under_maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}`}>{eq.status}</span></td>
                                <td className="px-6 py-4 text-sm">
                                    <Link href={`/equipment/${eq.equipment_id}/edit`} className="text-yellow-600 mr-3">Edit</Link>
                                    <button onClick={() => handleDelete(eq.equipment_id)} className="text-red-600">Delete</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
