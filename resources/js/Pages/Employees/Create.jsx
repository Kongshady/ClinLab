import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create({ sections }) {
    const { data, setData, post, processing, errors } = useForm({
        section_id: '', firstname: '', middlename: '', lastname: '', username: '', password: '', position: '', role: 'Staff',
    });

    return (
        <AppLayout title="Add New Employee">
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form onSubmit={(e) => { e.preventDefault(); post('/employees'); }} className="space-y-6 max-w-2xl">
                    <div className="grid grid-cols-3 gap-4">
                        <div><label className="block text-sm font-medium text-gray-700">First Name *</label><input type="text" value={data.firstname} onChange={(e) => setData('firstname', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                        <div><label className="block text-sm font-medium text-gray-700">Middle Name</label><input type="text" value={data.middlename} onChange={(e) => setData('middlename', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" /></div>
                        <div><label className="block text-sm font-medium text-gray-700">Last Name *</label><input type="text" value={data.lastname} onChange={(e) => setData('lastname', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                    </div>
                    <div><label className="block text-sm font-medium text-gray-700">Username *</label><input type="text" value={data.username} onChange={(e) => setData('username', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                    <div><label className="block text-sm font-medium text-gray-700">Password *</label><input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                    <div><label className="block text-sm font-medium text-gray-700">Position *</label><input type="text" value={data.position} onChange={(e) => setData('position', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                    <div><label className="block text-sm font-medium text-gray-700">Role *</label><input type="text" value={data.role} onChange={(e) => setData('role', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required /></div>
                    <div><label className="block text-sm font-medium text-gray-700">Section</label><select value={data.section_id} onChange={(e) => setData('section_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"><option value="">Select</option>{sections.map(s => <option key={s.section_id} value={s.section_id}>{s.label}</option>)}</select></div>
                    <div className="flex justify-end space-x-4">
                        <Link href="/employees" className="px-4 py-2 bg-gray-300 rounded-md text-xs uppercase">Cancel</Link>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Save</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
