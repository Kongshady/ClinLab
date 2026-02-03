import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Items({ items, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const handleDelete = (id) => { if (confirm('Are you sure?')) { router.delete(`/items/${id}`, { onSuccess: () => setFlashMessage('Item deleted successfully.') }); } };

    return (
        <AppLayout title="Items/Inventory">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <div className="flex justify-between mb-6">
                    <h1 className="text-2xl font-semibold">Items/Inventory</h1>
                    <Link href="/items/create" className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Add New</Link>
                </div>
                {flashMessage && <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{flashMessage}</div>}
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {items.data.map(item => (
                            <tr key={item.item_id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 text-sm">{item.item_id}</td>
                                <td className="px-6 py-4 text-sm font-medium">{item.label}</td>
                                <td className="px-6 py-4 text-sm">{item.section?.label}</td>
                                <td className="px-6 py-4 text-sm">{item.unit}</td>
                                <td className="px-6 py-4 text-sm">{item.reorder_level}</td>
                                <td className="px-6 py-4 text-sm">
                                    <Link href={`/items/${item.item_id}/edit`} className="text-yellow-600 mr-3">Edit</Link>
                                    <button onClick={() => handleDelete(item.item_id)} className="text-red-600">Delete</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
