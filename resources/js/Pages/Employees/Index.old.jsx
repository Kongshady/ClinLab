import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Employees({ employees, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);

    const handleDelete = (id) => {
        if (confirm('Are you sure?')) {
            router.delete(`/employees/${id}`, {
                onSuccess: () => setFlashMessage('Employee deleted successfully.')
            });
        }
    };

    return (
        <AppLayout title="Employees">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-semibold text-gray-900">Employees</h1>
                        <Link href="/employees/create" className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">Add New Employee</Link>
                    </div>
                    {flashMessage && <div className="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{flashMessage}</div>}
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {employees.data.map((emp) => (
                                    <tr key={emp.employee_id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{emp.employee_id}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">{emp.full_name}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{emp.username}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{emp.position}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{emp.section?.label || 'N/A'}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <Link href={`/employees/${emp.employee_id}/edit`} className="text-yellow-600 hover:text-yellow-900 mr-3">Edit</Link>
                                            <button onClick={() => handleDelete(emp.employee_id)} className="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
