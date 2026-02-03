import { useForm, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { useState } from 'react';

export default function Equipment({ equipment, sections, flash }) {
    const [flashMessage, setFlashMessage] = useState(flash?.success);
    const [searchTerm, setSearchTerm] = useState('');
    
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        model: '',
        serial_no: '',
        section_id: '',
        status: 'operational',
        purchase_date: '',
        supplier: '',
        remarks: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/equipment', {
            onSuccess: () => {
                reset();
                setFlashMessage('Equipment added successfully.');
            }
        });
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this equipment?')) {
            router.delete(`/equipment/${id}`, {
                onSuccess: () => setFlashMessage('Equipment deleted successfully.')
            });
        }
    };

    const filteredEquipment = equipment.data.filter(e => 
        e.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (e.model && e.model.toLowerCase().includes(searchTerm.toLowerCase())) ||
        (e.serial_no && e.serial_no.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    return (
        <AppLayout title="Equipment Management">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    {flashMessage && (
                        <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            {flashMessage}
                        </div>
                    )}

                    {/* Add New Equipment Form */}
                    <div className="mb-8">
                        <h2 className="text-xl font-semibold text-gray-800 mb-4">Add New Equipment</h2>
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Equipment Name *</label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Model</label>
                                    <input
                                        type="text"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                                    <input
                                        type="text"
                                        value={data.serial_no}
                                        onChange={(e) => setData('serial_no', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                    <select
                                        value={data.section_id}
                                        onChange={(e) => setData('section_id', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    >
                                        <option value="">Select Section</option>
                                        {sections && sections.map((section) => (
                                            <option key={section.section_id} value={section.section_id}>
                                                {section.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                        required
                                    >
                                        <option value="operational">Operational</option>
                                        <option value="under_maintenance">Under Maintenance</option>
                                        <option value="decommissioned">Decommissioned</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                                    <input
                                        type="date"
                                        value={data.purchase_date}
                                        onChange={(e) => setData('purchase_date', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                                    <input
                                        type="text"
                                        value={data.supplier}
                                        onChange={(e) => setData('supplier', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                    <input
                                        type="text"
                                        value={data.remarks}
                                        onChange={(e) => setData('remarks', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50"
                            >
                                Add Equipment
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
                            placeholder="Search by name, model, or serial number..."
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        />
                    </div>

                    {/* Equipment List */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Equipment List</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Model</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Serial No</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {filteredEquipment.length > 0 ? filteredEquipment.map((equip) => (
                                        <tr key={equip.equipment_id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{equip.name}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{equip.model || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{equip.serial_no || '-'}</td>
                                            <td className="px-6 py-4 text-sm text-gray-600">{equip.section?.label || 'N/A'}</td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                    equip.status === 'operational' ? 'bg-green-100 text-green-800' :
                                                    equip.status === 'under_maintenance' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {equip.status.replace('_', ' ').toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm space-x-2">
                                                <button
                                                    onClick={() => router.get(`/equipment/${equip.equipment_id}/edit`)}
                                                    className="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(equip.equipment_id)}
                                                    className="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-4 text-sm text-gray-500 text-center">
                                                No equipment found.
                                            </td>
                                        </tr>
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
