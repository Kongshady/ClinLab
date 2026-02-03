import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Edit({ patient }) {
    const { data, setData, put, processing, errors } = useForm({
        patient_type: patient.patient_type || 'External',
        firstname: patient.firstname || '',
        middlename: patient.middlename || '',
        lastname: patient.lastname || '',
        birthdate: patient.birthdate ? new Date(patient.birthdate).toISOString().split('T')[0] : '',
        gender: patient.gender || '',
        contact_number: patient.contact_number || '',
        address: patient.address || '',
    });

    return (
        <AppLayout title="Edit Patient">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-8">
                    <h2 className="text-2xl font-semibold text-gray-800 mb-6">Edit Patient Information</h2>
                    <form onSubmit={(e) => { e.preventDefault(); put(`/patients/${patient.patient_id}`); }} className="space-y-6 max-w-4xl">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Patient Type <span className="text-red-500">*</span></label>
                                <select
                                    value={data.patient_type}
                                    onChange={(e) => setData('patient_type', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    required
                                >
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">First Name <span className="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    value={data.firstname} 
                                    onChange={(e) => setData('firstname', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                    required 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                <input 
                                    type="text" 
                                    value={data.middlename} 
                                    onChange={(e) => setData('middlename', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Last Name <span className="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    value={data.lastname} 
                                    onChange={(e) => setData('lastname', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                    required 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Date of Birth <span className="text-red-500">*</span></label>
                                <input 
                                    type="date" 
                                    value={data.birthdate} 
                                    onChange={(e) => setData('birthdate', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                    required 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Gender <span className="text-red-500">*</span></label>
                                <select
                                    value={data.gender}
                                    onChange={(e) => setData('gender', e.target.value)}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                                <input 
                                    type="text" 
                                    value={data.contact_number} 
                                    onChange={(e) => setData('contact_number', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <input 
                                    type="text" 
                                    value={data.address} 
                                    onChange={(e) => setData('address', e.target.value)} 
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" 
                                />
                            </div>
                        </div>
                        <div className="flex items-center justify-end space-x-4 pt-4">
                            <Link href="/patients" className="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium">Cancel</Link>
                            <button type="submit" disabled={processing} className="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium disabled:opacity-50">
                                {processing ? 'Updating...' : 'Update Patient'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
