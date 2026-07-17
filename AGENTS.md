<!-- context7 -->
Use Context7 MCP to fetch current documentation whenever the user asks about a library, framework, SDK, API, CLI tool, or cloud service — even well-known ones like React, Next.js, Prisma, Express, Tailwind, Django, or Spring Boot. This includes API syntax, configuration, version migration, library-specific debugging, setup instructions, and CLI tool usage. Use even when you think you know the answer — your training data may not reflect recent changes. Prefer this over web search for library docs.

Do not use for: refactoring, writing scripts from scratch, debugging business logic, code review, or general programming concepts.

## Steps

1. Always start with `resolve-library-id` using the library name and the user's question, unless the user provides an exact library ID in `/org/project` format
2. Pick the best match (ID format: `/org/project`) by: exact name match, description relevance, code snippet count, source reputation (High/Medium preferred), and benchmark score (higher is better). If results don't look right, try alternate names or queries (e.g., "next.js" not "nextjs", or rephrase the question). Use version-specific IDs when the user mentions a version
3. `query-docs` with the selected library ID and the user's full question (not single words), scoped to a single concept. If the question spans multiple distinct concepts (e.g. routing and auth and caching), make a separate `query-docs` call per concept with the same library ID, unless the question is about how the concepts interact — combined queries dilute ranking and return shallow results for each topic
4. Answer using the fetched docs
<!-- context7 -->


# فكرة المشروع

بيان تطبيق ويب لتتبع المصاريف الشخصية مدعوم بالذكاء الاصطناعي

---

## الفئة المستهدفة
- أفراد (استخدام شخصي)

---

## المنصة والتقنية
- موقع ويب
- تخزين سحابي
- مصادقة بكلمة مرور
- دعم اللغتين العربية والإنجليزية
- دعم الوضع الفاتح والداكن
- العملة: ريال سعودي

---

## المتطلبات الوظيفية

### 1. تسجيل المصاريف
- إدخال يدوي للمصاريف
- تحليل الفواتير بالصور (OCR)
- تصنيفات: طعام وشراب، مواصلات، فواتير، ترفيه، صحة، تعليم، سكن وإيجارات، أخرى

### 2. الميزانيات
- ميزانية يومية
- ميزانية أسبوعية
- ميزانية شهرية
- ميزانية لكل تصنيف

### 3. التنبيهات
- تنبيه عند تجاوز الميزانية
- تذكير بالفواتير الدورية
- ملخص دوري (يومي/أسبوعي)
- تنبيه عند وجود مصاريف غير معتادة

### 4. التقارير والتحليلات
- تقارير يومية، أسبوعية، شهرية، سنوية
- رسوم بيانية تفاعلية (أعمدة، دائري، خطي)
- مقارنة بين الفترات الزمنية
- تصدير التقارير بصيغة PDF

### 5. أهداف الادخار
- تحديد أهداف ادخار
- متابعة التقدم نحو الأهداف

---

## الذكاء الاصطناعي

### الميزات التحليلية
- تحليل أنماط الصرف
- توقع المصاريف المستقبلية
- توصيات لتقليل المصاريف
- كشف المصاريف غير المعتادة

### المحادثة الذكية (Agentic AI)
مساعد ذكي قادر على:
- الإجابة عن الاستفسارات المتعلقة بالمصاريف
- إضافة مصاريف جديدة عن طريق المحادثة
- إنشاء فئات جديدة للمصاريف
- تعديل الميزانيات والإعدادات
- التحكم الكامل بالتطبيق عبر الأوامر النصية

---

## ملاحظة
- لا يوجد ربط مع بنوك أو محافظ رقمية


# متطلبات MVP (Must Have)

1. **تسجيل الدخول** - Email + Password
2. **إدخال المصاريف والدخل** - يدوي مع فئات
3. **تصنيف تلقائي بالذكاء الاصطناعي** - عند إدخال المصروف
4. **وكيل ذكاء اصطناعي محادثي** - يسأل ويجاوب عن الفلوس، يضيف مصاريف، ينصح، يكتشف الحالات الشاذة
5. **تصورات بيانية** - رسوم بيانية تفاعلية للمصروفات والدخل
6. **Cloud Sync** - حفظ البيانات في السحابة

---

# MoSCoW Prioritization

## Must Have (MVP)
- Authentication (Email + Password)
- Manual expense/income entry with categories
- AI chat assistant (Q&A, add transactions, anomaly detection, actionable insights)
- Rich visualizations (pie charts, bar charts, trends)
- Cloud data sync
- Arabic UI

## Should Have
- AI auto-categorization on entry
- Spending trends and insights
- Data export PDF

## Could Have
- Bank import / Open Banking (STC Pay)
- Multi-currency support
- Receipt OCR scanning
- English language support
- Expense splitting

## Won't Have (v1)
- Attachments / receipt images
- Tags (beyond categories)
- Savings goals
- Budgeting/envelope system
- Social/multi-user features

---

# AI Agent Capabilities (MVP)

1. **Financial Q&A** - "كم صرفت على الأكل الشهر اللي فات؟"
2. **Add transactions via chat** - "أضف ٤٥ ريال مشتريات بقالة"
3. **Budgeting advice** - "صرفك على المطاعم زائد هذا الشهر"
4. **Anomaly detection** - "هذه المصروف ٥٠٠ ريال غير معتاد"
5. **Trigger actions** - "صنف لي هذا المصروف" أو "أظهر تقرير"

# اختيار التقنيات

- Laravel
- Inertia (laravel svelte template)
- TailwindCSS