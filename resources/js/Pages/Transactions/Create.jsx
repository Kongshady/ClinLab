import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create({ patients }) {
    const { data, setData, post, processing } = useForm({ client_id: '', or_number: '', client_designation: '' });
    return (
        <AppLayout title="New Transaction">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <form onSubmit={(e) => { e.preventDefault(); post('/transactions'); }} className="space-y-4 max-w-2xl">
                    <div><label className="block text-sm font-medium">Patient *</label><select value={data.client_id} onChange={(e) => setData('client_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required><option value="">Select</option>{patients.map(p => <option key={p.patient_id} value={p.patient_id}>{p.firstname} {p.lastname}</option>)}</select></div>
                    <div><label className="block text-sm font-medium">OR Number *</label><input type="number" value={data.or_number} onChange={(e) => setData('or_number', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required /></div>
                    <div><label className="block text-sm font-medium">Client Designation</label><input type="text" value={data.client_designation} onChange={(e) => setData('client_designation', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    <div className="flex justify-end space-x-4">
                        <Link href="/transactions" className="px-4 py-2 bg-gray-300 rounded-md text-xs uppercase">Cancel</Link>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Save</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
