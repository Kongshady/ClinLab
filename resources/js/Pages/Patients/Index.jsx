import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Patients({ patients, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        patient_type: 'External',
        firstname: '',
        middlename: '',
        lastname: '',
        birthdate: '',
        gender: '',
        contact_number: '',
        address: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/patients', {
            onSuccess: () => {
                reset();
                setFlashMessage('Patient added successfully.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this patient?')) {
            router.delete(`/patients/${id}`, {
                onSuccess: () => setFlashMessage('Patient deleted successfully.')
            });
        }
    };

    const filteredPatients = patients.data.filter(p => 
        p.firstname.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.lastname.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (p.contact_number && p.contact_number.includes(searchTerm))
    );

    return (
        <AppLayout title="Patient Profile Management">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    {flashMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            {flashMessage}
                        </div>
                    )}

                    {/* Add New Patient Form */}
                    <div className="mb-8">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Add New Patient</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Patient Type *</label>
                                    <select
                                        value={data.patient_type}
                                        onChange={(e) => setData('patient_type', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="Internal">Internal</option>
                                        <option value="External">External</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input
                                        type="text"
                                        value={data.firstname}
                                        onChange={(e) => setData('firstname', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input
                                        type="text"
                                        value={data.middlename}
                                        onChange={(e) => setData('middlename', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input
                                        type="text"
                                        value={data.lastname}
                                        onChange={(e) => setData('lastname', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                                    <input
                                        type="date"
                                        value={data.birthdate}
                                        onChange={(e) => setData('birthdate', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                                    <select
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
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
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input
                                        type="text"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Add Patient
                            </button>
                        </form>
                    </div>

                    {/* Search Bar */}
                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search by name or contact..."
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        />
                    </div>

                    {/* Patients List */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Patients List</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Birthdate</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Gender</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Address</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredPatients.length > 0 ? filteredPatients.map((patient) => (
                                        <tr key={patient.patient_id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{patient.patient_type}</td>
                                            <td className="px-6 py-4 text-sm text-gray-900">{patient.full_name}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{new Date(patient.birthdate).toLocaleDateString()}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{patient.gender}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{patient.contact_number || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{patient.address || '-'}</td>
                                            <td className="px-6 py-4 text-sm space-x-2">
                                                <button
                                                    onClick={() => router.get(`/patients/${patient.patient_id}/edit`)}
                                                    className="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(patient.patient_id)}
                                                    className="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr><td colSpan="7" className="px-6 py-8 text-center text-sm text-gray-500">No patients found.</td></tr>
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
