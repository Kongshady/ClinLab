import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Physicians({ physicians, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        physician_name: '',
        specialization: '',
        contact_number: '',
        email: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/physicians', {
            onSuccess: () => {
                reset();
                setFlashMessage('Physician added successfully.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this physician?')) {
            router.delete(`/physicians/${id}`, {
                onSuccess: () => setFlashMessage('Physician deleted successfully.')
            });
        }
    };

    const filteredPhysicians = physicians.data.filter(p => 
        p.physician_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (p.specialization && p.specialization.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (p.contact_number && p.contact_number.includes(searchTerm)) ||
        (p.email && p.email.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    return (
        <AppLayout title="Physician Management">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    {flashMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            {flashMessage}
                        </div>
                    )}

                    {/* Add New Physician Form */}
                    <div className="mb-8">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Add New Physician</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Physician Name *</label>
                                    <input
                                        type="text"
                                        value={data.physician_name}
                                        onChange={(e) => setData('physician_name', e.target.value)}
                                        placeholder="Dr. Juan Dela Cruz"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                    {errors.physician_name && <p className="mt-1 text-xs text-red-600">{errors.physician_name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                                    <input
                                        type="text"
                                        value={data.specialization}
                                        onChange={(e) => setData('specialization', e.target.value)}
                                        placeholder="e.g., Pathologist"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                                    <input
                                        type="text"
                                        value={data.contact_number}
                                        onChange={(e) => setData('contact_number', e.target.value)}
                                        placeholder="09171234567"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="doctor@clinic.com"
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Add Physician
                            </button>
                        </form>
                    </div>

                    {/* Search Bar */}
                    <div className="mb-6">
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input
                                    type="text"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    placeholder="Search by name, contact, or email..."
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Physicians List */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Physicians List</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Specialization</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredPhysicians.length > 0 ? filteredPhysicians.map((physician) => (
                                        <tr key={physician.physician_id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{physician.physician_name}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{physician.specialization || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{physician.contact_number || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{physician.email || '-'}</td>
                                            <td className="px-6 py-4 text-sm space-x-2">
                                                <button
                                                    onClick={() => router.get(`/physicians/${physician.physician_id}/edit`)}
                                                    className="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(physician.physician_id)}
                                                    className="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr><td colSpan="5" className="px-6 py-8 text-center text-sm text-gray-500">No physicians found.</td></tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
