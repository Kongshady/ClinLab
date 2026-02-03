import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create({ sections }) {
    const { data, setData, post, processing } = useForm({ name: '', model: '', serial_no: '', section_id: '', status: 'operational', purchase_date: '', supplier: '', remarks: '' });
    return (
        <AppLayout title="Add New Equipment">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <form onSubmit={(e) => { e.preventDefault(); post('/equipment'); }} className="space-y-4 max-w-2xl">
                    <div><label className="block text-sm font-medium">Equipment Name *</label><input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required /></div>
                    <div className="grid grid-cols-2 gap-4">
                        <div><label className="block text-sm font-medium">Model</label><input type="text" value={data.model} onChange={(e) => setData('model', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                        <div><label className="block text-sm font-medium">Serial Number</label><input type="text" value={data.serial_no} onChange={(e) => setData('serial_no', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    </div>
                    <div><label className="block text-sm font-medium">Section</label><select value={data.section_id} onChange={(e) => setData('section_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300"><option value="">Select</option>{sections.map(s => <option key={s.section_id} value={s.section_id}>{s.label}</option>)}</select></div>
                    <div><label className="block text-sm font-medium">Status *</label><select value={data.status} onChange={(e) => setData('status', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required><option value="operational">Operational</option><option value="under_maintenance">Under Maintenance</option><option value="decommissioned">Decommissioned</option></select></div>
                    <div><label className="block text-sm font-medium">Purchase Date</label><input type="date" value={data.purchase_date} onChange={(e) => setData('purchase_date', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    <div><label className="block text-sm font-medium">Supplier</label><input type="text" value={data.supplier} onChange={(e) => setData('supplier', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    <div><label className="block text-sm font-medium">Remarks</label><textarea value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" rows="3"></textarea></div>
                    <div className="flex justify-end space-x-4">
                        <Link href="/equipment" className="px-4 py-2 bg-gray-300 rounded-md text-xs uppercase">Cancel</Link>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Save</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
