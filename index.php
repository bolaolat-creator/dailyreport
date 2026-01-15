import React, { useState, useEffect } from 'react';
import { 
  Clipboard, 
  Printer, 
  Send, 
  CheckCircle, 
  AlertCircle, 
  User, 
  Calendar, 
  Clock, 
  Users, 
  ShieldAlert,
  FileText,
  Activity,
  History
} from 'lucide-react';

// ============================================================
// 1. CONFIGURATION: PASTE YOUR GOOGLE WEB APP URL HERE
// ============================================================
const GOOGLE_SHEET_WEBHOOK_URL = "https://script.google.com/macros/s/AKfycbycz3E3lszxT1ZnLPRFvWV6uhTbq37YkkYZ1bCP5pMN_Z_1GLP5rX5IYGHND1C3VQsF6A/exec"; 
// ============================================================

const App = () => {
  // --- State Management ---
  const [data, setData] = useState(() => {
    const saved = localStorage.getItem('daily_report_draft');
    return saved ? JSON.parse(saved) : {
      date: new Date().toISOString().split('T')[0],
      teacherName: '',
      classGrade: '',
      // Attendance
      total: '',
      present: '',
      absent: '',
      sickIn: '',
      sentHome: '',
      disciplinary: '',
      // Health
      hasHealthComplaint: 'No',
      hasAccident: 'No',
      incidentDetails: '',
      // Comments
      comments: '',
      signature: '',
      receivedBy: '',
      submittedTime: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    };
  });

  const [status, setStatus] = useState({ type: '', msg: '' });
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Save draft to localStorage on change
  useEffect(() => {
    localStorage.setItem('daily_report_draft', JSON.stringify(data));
  }, [data]);

  // Auto-calculate attendance logic
  useEffect(() => {
    if (data.total && data.absent !== '' && data.present === '') {
      const calcPresent = parseInt(data.total) - parseInt(data.absent);
      if (calcPresent >= 0) setData(prev => ({ ...prev, present: calcPresent }));
    }
  }, [data.total, data.absent]);

  // --- Handlers ---
  const handleChange = (field, value) => {
    setData(prev => ({ ...prev, [field]: value }));
  };

  const showToast = (msg, type) => {
    setStatus({ msg, type });
    setTimeout(() => setStatus({ msg: '', type: '' }), 4000);
  };

  const handleSubmit = async () => {
    if (!GOOGLE_SHEET_WEBHOOK_URL) {
      showToast("System Error: Webhook URL not configured.", "error");
      return;
    }
    if (!data.teacherName || !data.classGrade) {
      showToast("Please provide Teacher Name and Class.", "error");
      return;
    }

    setIsSubmitting(true);
    try {
      // POST to Google Apps Script
      await fetch(GOOGLE_SHEET_WEBHOOK_URL, {
        method: 'POST',
        mode: 'no-cors',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, timestamp: new Date().toLocaleString() })
      });

      showToast("Report submitted successfully!", "success");
      // Optional: Clear storage after success
      // localStorage.removeItem('daily_report_draft');
    } catch (err) {
      showToast("Submission failed. Check connection.", "error");
    } finally {
      setIsSubmitting(false);
    }
  };

  const copySummary = () => {
    const text = `Daily Report: ${data.date}\nTeacher: ${data.teacherName}\nClass: ${data.classGrade}\nAttendance: ${data.present}/${data.total}\nComments: ${data.comments}`;
    navigator.clipboard.writeText(text);
    showToast("Summary copied to clipboard", "success");
  };

  // --- UI Components ---
  const InputRow = ({ label, field, type = "number", placeholder = "0" }) => (
    <div className="flex flex-col sm:flex-row sm:items-center justify-between py-3 border-b border-slate-100 last:border-0">
      <span className="text-sm font-medium text-slate-600 mb-1 sm:mb-0">{label}</span>
      <input 
        type={type}
        value={data[field]}
        onChange={(e) => handleChange(field, e.target.value)}
        placeholder={placeholder}
        className="w-full sm:w-24 p-2 bg-slate-50 border border-slate-200 rounded-md text-center focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
      />
    </div>
  );

  return (
    <div className="min-h-screen bg-slate-100 font-sans text-slate-900 pb-12">
      {/* Top Banner */}
      <div className="bg-indigo-700 text-white pt-8 pb-16 px-4 text-center">
        <h1 className="text-2xl font-bold tracking-tight uppercase sm:text-3xl">Teacher's Daily Report</h1>
        <p className="text-indigo-200 text-sm mt-1">Digital Submission System v2.0</p>
      </div>

      {/* Main Container */}
      <main className="max-w-3xl mx-auto -mt-10 px-4">
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
          
          {/* Section 1: Basic Info */}
          <div className="p-6 bg-slate-50 border-b border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider"><Calendar className="w-3 h-3"/> Date</label>
              <input type="date" value={data.date} onChange={e => handleChange('date', e.target.value)} className="w-full p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" />
            </div>
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider"><Users className="w-3 h-3"/> Class/Grade</label>
              <input type="text" value={data.classGrade} onChange={e => handleChange('classGrade', e.target.value)} placeholder="e.g. 7A" className="w-full p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" />
            </div>
            <div className="space-y-1">
              <label className="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider"><User className="w-3 h-3"/> Teacher</label>
              <input type="text" value={data.teacherName} onChange={e => handleChange('teacherName', e.target.value)} placeholder="Full Name" className="w-full p-2.5 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm" />
            </div>
          </div>

          <div className="p-6 space-y-10">
            
            {/* Section A: Attendance */}
            <section>
              <div className="flex items-center gap-2 mb-4">
                <div className="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg"><Activity className="w-5 h-5"/></div>
                <h2 className="text-lg font-bold text-slate-800 uppercase tracking-tight">Attendance Summary</h2>
              </div>
              <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <InputRow label="Total Students in Class" field="total" />
                <InputRow label="Number of Students Present" field="present" />
                <InputRow label="Number of Students Absent" field="absent" />
                <InputRow label="Students Sick (In School)" field="sickIn" />
                <InputRow label="Students Sent Home Sick" field="sentHome" />
              </div>
              <div className="mt-4">
                <label className="text-sm font-semibold text-slate-700 mb-2 block">Disciplinary Issues</label>
                <textarea 
                  value={data.disciplinary}
                  onChange={e => handleChange('disciplinary', e.target.value)}
                  className="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none min-h-[80px] text-sm"
                  placeholder="Record any behavior incidents..."
                />
              </div>
            </section>

            {/* Section B: Health & Welfare */}
            <section>
              <div className="flex items-center gap-2 mb-4">
                <div className="bg-rose-100 text-rose-600 p-1.5 rounded-lg"><ShieldAlert className="w-5 h-5"/></div>
                <h2 className="text-lg font-bold text-slate-800 uppercase tracking-tight">Health & Welfare</h2>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {['hasHealthComplaint', 'hasAccident'].map((field) => (
                  <div key={field} className="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <span className="text-sm font-medium text-slate-700">{field === 'hasHealthComplaint' ? 'Health Complaints?' : 'Accidents/Injuries?'}</span>
                    <div className="flex gap-1 bg-slate-200 p-1 rounded-lg">
                      {['Yes', 'No'].map(choice => (
                        <button 
                          key={choice} 
                          onClick={() => handleChange(field, choice)}
                          className={`px-4 py-1.5 rounded-md text-xs font-bold transition-all ${data[field] === choice ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-700'}`}
                        >{choice}</button>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
              {(data.hasHealthComplaint === 'Yes' || data.hasAccident === 'Yes') && (
                <div className="mt-4 animate-in slide-in-from-top-2">
                  <textarea 
                    value={data.incidentDetails}
                    onChange={e => handleChange('incidentDetails', e.target.value)}
                    className="w-full p-4 border-2 border-rose-100 bg-rose-50 rounded-xl focus:ring-2 focus:ring-rose-500 outline-none text-sm placeholder-rose-300"
                    placeholder="Provide mandatory incident description here..."
                  />
                </div>
              )}
            </section>

            {/* Section C: General Comments */}
            <section>
              <div className="flex items-center gap-2 mb-4">
                <div className="bg-emerald-100 text-emerald-600 p-1.5 rounded-lg"><FileText className="w-5 h-5"/></div>
                <h2 className="text-lg font-bold text-slate-800 uppercase tracking-tight">Teacher Comments</h2>
              </div>
              <textarea 
                value={data.comments}
                onChange={e => handleChange('comments', e.target.value)}
                className="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none min-h-[120px] text-sm"
                placeholder="Notes on teaching progress, general welfare, or reminders..."
              />
            </section>

            {/* Signatures */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 pt-8 border-t border-slate-100">
              <div className="space-y-4">
                <div className="relative">
                  <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Teacher's Digital Signature</label>
                  <input type="text" value={data.signature} onChange={e => handleChange('signature', e.target.value)} placeholder="Type Name" className="w-full bg-transparent border-b-2 border-slate-200 focus:border-indigo-600 outline-none font-serif italic text-xl py-1" />
                </div>
                <div>
                  <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Received By</label>
                  <input type="text" value={data.receivedBy} onChange={e => handleChange('receivedBy', e.target.value)} className="w-full bg-transparent border-b-2 border-slate-200 focus:border-indigo-600 outline-none py-1" />
                </div>
              </div>
              <div className="flex flex-col items-end justify-center">
                <div className="text-right">
                  <label className="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Time of Submission</label>
                  <div className="flex items-center gap-2 text-3xl font-mono font-bold text-slate-700">
                    <Clock className="w-6 h-6 text-indigo-500" />
                    {data.submittedTime}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Action Bar */}
          <div className="p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div className="flex gap-2 w-full sm:w-auto">
              <button onClick={() => window.print()} className="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                <Printer className="w-4 h-4"/> PDF
              </button>
              <button onClick={copySummary} className="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                <Clipboard className="w-4 h-4"/> Copy
              </button>
            </div>
            
            <button 
              onClick={handleSubmit}
              disabled={isSubmitting}
              className={`w-full sm:w-auto flex items-center justify-center gap-3 px-12 py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl hover:bg-indigo-700 active:scale-95 transition-all ${isSubmitting ? 'opacity-50' : ''}`}
            >
              {isSubmitting ? 'Sending...' : 'Submit Report'} <Send className="w-4 h-4"/>
            </button>
          </div>
        </div>

        <p className="mt-8 text-center text-slate-400 text-[10px] uppercase font-bold tracking-widest">
          Secured Digital Artifact • Internal School Use Only
        </p>
      </main>

      {/* Toasts */}
      {status.msg && (
        <div className={`fixed bottom-8 left-1/2 -translate-x-1/2 px-8 py-4 rounded-full shadow-2xl flex items-center gap-3 text-white font-bold z-50 animate-in slide-in-from-bottom-10 duration-300 ${status.type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'}`}>
          {status.type === 'success' ? <CheckCircle className="w-5 h-5"/> : <AlertCircle className="w-5 h-5"/>}
          {status.msg}
        </div>
      )}

      <style>{`
        @media print {
          body { background: white; padding: 0; }
          main { margin: 0; max-width: 100%; }
          button, .bg-indigo-700, .bg-slate-50, .fixed { display: none !important; }
          .bg-white { border: none; shadow: none; }
          input, textarea { border: none !important; background: transparent !important; }
        }
      `}</style>
    </div>
  );
};

export default App;
